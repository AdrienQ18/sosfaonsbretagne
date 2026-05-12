<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use App\Form\DonationType;
use App\Repository\DonationRepository;
use App\Service\DonationPdfService;
use App\Service\HelloAssoService;
use App\Service\HelloAssoWebhookService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class DonationController extends AbstractController
{
    /**
     * Affiche et traite le formulaire de don.
     *
     * Fonctionnement :
     * - Si l'utilisateur est connecté, ses informations sont copiées dans la donation.
     * - Si l'utilisateur n'est pas connecté, il doit remplir ses informations dans le formulaire.
     * - Après validation du formulaire, la donation est créée avec le statut DONATION_PASSEE.
     * - L'utilisateur est ensuite redirigé vers HelloAsso pour effectuer le paiement.
     */
    #[Route('/donation', name: 'donation', methods: ['GET', 'POST'])]
    public function indexDonation(
        Request                $request,
        EntityManagerInterface $entityManager,
        HelloAssoService       $helloAssoService
    ): Response
    {
        $newDonation = new Donation();

        /*
         * Si un utilisateur est connecté, on garde un lien avec son compte.
         *
         * On copie aussi ses informations dans Donation afin de conserver
         * une trace exacte des données utilisées au moment du don.
         * Cela évite qu'un changement futur du profil utilisateur modifie
         * les informations historiques du reçu fiscal.
         */
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if ($user) {
            $newDonation->setUser($user);
            $newDonation->setFirstname($user->getFirstname());
            $newDonation->setLastname($user->getLastname());
            $newDonation->setEmail($user->getEmail());
            $newDonation->setCity($user->getCity());
            $newDonation->setAddress($user->getAddress());
            $newDonation->setZipcode($user->getZipcode());
        }

        $formDonation = $this->createForm(DonationType::class, $newDonation);
        $formDonation->handleRequest($request);

        if ($formDonation->isSubmitted() && $formDonation->isValid()) {
            /*
             * Le don est créé avant le paiement.
             * À ce stade, il n'est pas encore validé : il est seulement "passé".
             */
            $newDonation->setDonationDate(new \DateTime());
            $newDonation->setStatus(DonationStatus::DONATION_PASSEE);

            $entityManager->persist($newDonation);
            $entityManager->flush();

            /*
             * Création du paiement HelloAsso.
             * Le service retourne l'URL vers laquelle l'utilisateur doit être redirigé.
             */
            $redirectUrl = $helloAssoService->createDonationCheckout($newDonation);

            return $this->redirect($redirectUrl);
        }

        return $this->render('donation/donation.html.twig', [
            'formDonation' => $formDonation->createView(),
        ]);
    }

    /**
     * Affiche la liste des donations dans l'administration.
     *
     * TODO sécurité :
     * Cette route devra être protégée pour être accessible uniquement aux admins.
     */
    #[Route('/admin/donation', name: 'admin_donation')]
    public function indexAdminDonation(DonationRepository $donationRepository): Response
    {
        return $this->render('donation/adminDonation.html.twig', [
            'donations' => $donationRepository->findAll(),
        ]);
    }

    #[Route('/admin/donation/{id}/pdf', name: 'admin_donation_pdf')]
    public function showDonationPdf(Donation $donation): Response
    {
        $pdfPath = $donation->getReceiptPdfPath();

        if (!$pdfPath || !file_exists($pdfPath)) {
            throw $this->createNotFoundException('Le reçu fiscal PDF est introuvable.');
        }

        return $this->file(
            new File($pdfPath),
            'recu-fiscal-' . $donation->getId() . '.pdf',
            ResponseHeaderBag::DISPOSITION_INLINE
        );
    }

    /**
     * Route appelée quand HelloAsso redirige l'utilisateur après un paiement terminé.
     *
     * Important :
     * Cette route ne valide pas réellement le don.
     * La validation fiable est faite dans le webhook HelloAsso.
     */
    #[Route('/donation/success/{id}', name: 'donation_success')]
    public function success(): Response
    {
        $this->addFlash('success', 'Paiement terminé. Votre don est en cours de validation.');

        return $this->redirect('http://localhost/sosfaonsbretagne/public/donation');
    }

    /**
     * Route appelée quand l'utilisateur annule ou revient depuis HelloAsso.
     *
     * Ici, on passe la donation en refusée car le paiement n'a pas été finalisé.
     */
    #[Route('/donation/cancel/{id}', name: 'donation_cancel')]
    public function cancel(Donation $donation, EntityManagerInterface $entityManager): Response
    {
        $donation->setStatus(DonationStatus::DONATION_REFUSEE);
        $entityManager->flush();

        $this->addFlash('error', 'Paiement annulé.');

        return $this->redirect('http://localhost/sosfaonsbretagne/public/donation');
    }

    /**
     * Webhook appelé automatiquement par HelloAsso après un événement de paiement.
     *
     * Cette route ne doit pas être appelée par un utilisateur classique.
     * Elle est appelée par HelloAsso en POST quand un paiement est validé,
     * refusé, annulé ou mis à jour.
     *
     * Étapes :
     * 1. On récupère le secret présent dans l'URL.
     * 2. On compare ce secret avec celui stocké dans .env.local.
     * 3. Si le secret est absent ou incorrect, on refuse la requête.
     * 4. On décode le contenu JSON envoyé par HelloAsso.
     * 5. On enregistre temporairement le contenu reçu dans un fichier de debug.
     * 6. Si le JSON est invalide, on retourne une erreur.
     * 7. Si tout est correct, on délègue le traitement au service HelloAssoWebhookService.
     * 8. Le service mettra à jour le statut de la donation.
     */
    #[Route('/helloasso/webhook', name: 'helloasso_webhook', methods: ['POST'])]
    public function helloAssoWebhook(
        Request                 $request,
        HelloAssoWebhookService $helloAssoWebhookService
    ): JsonResponse
    {
        /*
         * Récupère le secret envoyé dans l'URL du webhook.
         *
         * Exemple d'URL configurée côté HelloAsso :
         * /helloasso/webhook?secret=MON_SECRET
         */
        $receivedSecret = $request->query->get('secret');

        /*
         * Récupère le secret attendu depuis les variables d'environnement.
         *
         * Ce secret est défini dans .env.local :
         * HELLOASSO_WEBHOOK_SECRET=MON_SECRET
         */
        $expectedSecret = $_ENV['HELLOASSO_WEBHOOK_SECRET'];

        /*
         * Vérifie que le secret existe et qu'il correspond au secret attendu.
         *
         * hash_equals() est utilisé pour comparer deux valeurs sensibles
         * de manière plus sûre qu'un simple ===.
         */
        if (!$receivedSecret || !hash_equals($expectedSecret, $receivedSecret)) {
            return new JsonResponse([
                'error' => 'Notification non autorisée.',
            ], 403);
        }


        /*
         * Récupère le contenu brut envoyé par HelloAsso,
         * puis le transforme en tableau PHP.
         */
        $payload = json_decode($request->getContent(), true);


        /*
         * Si le JSON est vide ou invalide, on retourne une erreur 400.
         */
        if (!$payload) {
            return new JsonResponse([
                'error' => 'Le contenu reçu est invalide ou vide.',
            ], 400);
        }

        /*
         * Délègue toute la logique métier au service.
         *
         * Le service va :
         * - retrouver la donation grâce au donation_id
         * - lire le statut du paiement
         * - passer le don en validé ou refusé
         * - générer le PDF si le paiement est validé
         * - envoyer le reçu fiscal par email
         */
        $result = $helloAssoWebhookService->handle($payload);

        /*
         * Retourne la réponse JSON au format attendu.
         *
         * $result contient par exemple :
         * [
         *     'status' => 200,
         *     'message' => 'Statut de la donation mis à jour.'
         * ]
         */
        return new JsonResponse($result, $result['status']);
    }

    /**
     * Route temporaire de développement pour générer un reçu fiscal PDF.
     *
     * Elle permet de tester la génération du PDF sans repasser par un paiement HelloAsso.
     *
     * TODO sécurité :
     * À supprimer ou protéger avant la mise en production.
     */
    #[Route('/donation/{id}/pdf-test', name: 'donation_pdf_test')]
    public function testPdf(
        Donation           $donation,
        DonationPdfService $donationPdfService
    ): Response
    {
        $path = $donationPdfService->generateFiscalReceipt($donation);

        return new Response('PDF généré ici : ' . $path);
    }

    #[Route('/mail/test', name: 'mail_test')]
    public function testMail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('test@sosfaonsbretagne.fr')
            ->to('test@example.com')
            ->subject('Test Papercut')
            ->text('Ceci est un test Papercut.');

        $mailer->send($email);

        return new Response('Mail envoyé');
    }
}
