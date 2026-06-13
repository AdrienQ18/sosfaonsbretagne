<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Entity\User;
use App\Enum\DonationStatus;
use App\Enum\DonorType;
use App\Form\DonationFilterType;
use App\Form\DonationType;
use App\Repository\DonationRepository;
use App\Service\HelloAsso\HelloAssoService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use ZipArchive;

/**
 * Contrôleur de gestion des dons.
 *
 * Il permet :
 * - aux utilisateurs d'effectuer un don via HelloAsso ;
 * - de gérer les retours de paiement ;
 * - aux administrateurs de consulter les dons ;
 * - de visualiser ou télécharger les reçus fiscaux.
 */
final class DonationController extends AbstractController
{
    /**
     * Affiche et traite le formulaire de don.
     *
     * Si l'utilisateur est connecté, certaines informations
     * sont pré-remplies à partir de son compte.
     *
     * Après validation du formulaire, un paiement HelloAsso est créé.
     */
    #[Route('/donation', name: 'donation', methods: ['GET', 'POST'])]
    public function indexDonation(
        Request $request,
        EntityManagerInterface $entityManager,
        HelloAssoService $helloAssoService,
        LoggerInterface $logger,
    ): Response {
        $donation = new Donation();

        // Type de donateur par défaut : particulier.
        $donation->setDonorType(DonorType::PARTICULIER);

        $user = $this->getUser();

        // Si l'utilisateur est connecté, on pré-remplit le formulaire avec ses informations.
        if ($user) {
            /** @var User $user */
            $donation->setUser($user);
            $donation->setFirstname($user->getFirstname());
            $donation->setLastname($user->getLastname());
            $donation->setEmail($user->getEmail());
            $donation->setCity($user->getCity());
            $donation->setAddress($user->getAddress());
            $donation->setZipcode($user->getZipcode());
        }

        $form = $this->createForm(DonationType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Date et statut initial du don avant redirection vers HelloAsso.
            $donation->setDonationDate(new \DateTime());
            $donation->setStatus(DonationStatus::DONATION_PASSEE);

            $entityManager->persist($donation);
            $entityManager->flush();

            // On enregistre d'abord le don pour disposer d'un identifiant stable
            // utilisable par HelloAsso et par les journaux applicatifs.
            // Journalisation du don sans stocker d'information sensible en clair.
            $logger->info('helloasso.donation.created', [
                'donation_id' => $donation->getId(),
                'amount' => (string) $donation->getAmount(),
                'email_hash' => hash(
                    'sha256',
                    mb_strtolower(trim((string) $donation->getEmail()))
                ),
            ]);

            try {
                // Création du paiement HelloAsso et redirection de l'utilisateur.
                $checkout = $helloAssoService->createDonationCheckout($donation);

                return $this->redirect($checkout['redirectUrl']);
            } catch (\Throwable $e) {
                // En cas d'erreur HelloAsso, on supprime le don créé pour éviter une donnée incohérente.
                $entityManager->remove($donation);
                $entityManager->flush();

                $logger->error('helloasso.checkout.create_failed', [
                    'donation_id' => $donation->getId(),
                    'error' => $e->getMessage(),
                ]);

                $this->addFlash('error', 'Impossible de créer le paiement.');

                return $this->redirectToRoute('donation');
            }
        }

        return $this->render('donation/donation.html.twig', [
            'formDonation' => $form->createView(),
        ]);
    }

    /**
     * Page appelée lors du retour utilisateur après paiement HelloAsso.
     *
     * Attention : cette route ne valide pas définitivement le paiement.
     * La validation réelle doit être effectuée par le webhook HelloAsso.
     */
    #[Route('/donation/valider/{id}', name: 'donation_success', methods: ['GET'])]
    public function success(
        Donation $donation,
        Request $request,
        LoggerInterface $logger
    ): Response {
        // Journalisation du retour utilisateur depuis HelloAsso.
        // Ce retour peut arriver avant le webhook : il ne doit donc pas marquer
        // le paiement comme valide à lui seul.
        $logger->info('helloasso.return_url.hit', [
            'donation_id' => $donation->getId(),
            'query' => $request->query->all(),
        ]);

        $this->addFlash(
            'success',
            'Paiement en cours de validation. Votre reçu fiscal sera envoyé par email.'
        );

        return $this->redirectToRoute('main_home');
    }

    /**
     * Page appelée lorsque l'utilisateur annule son paiement.
     */
    #[Route('/donation/annuler/{id}', name: 'donation_cancel', methods: ['GET'])]
    public function cancel(
        Donation $donation,
        EntityManagerInterface $entityManager
    ): Response {
        // Le don est marqué comme refusé/annulé.
        $donation->setStatus(DonationStatus::DONATION_REFUSEE);
        $entityManager->flush();

        $this->addFlash('error', 'Paiement annulé.');

        return $this->redirectToRoute('donation');
    }

    /**
     * Affiche la liste des dons côté administration.
     *
     * Les dons sont filtrables, paginés, et le total des dons validés
     * est calculé selon les filtres appliqués.
     */
    #[Route('/admin/donation', name: 'admin_donation')]
    public function indexAdminDonation(
        DonationRepository $donationRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Formulaire de filtre en GET pour conserver les critères dans l'URL.
        $filterForm = $this->createForm(DonationFilterType::class, null, [
            'method' => 'GET',
        ]);

        $filterForm->handleRequest($request);

        $filters = $filterForm->getData() ?? [];

        // Calcul du total uniquement pour les dons validés.
        $totalValidatedAmount = $donationRepository->getValidatedTotalAmount($filters);

        // Requête de recherche des dons selon les filtres.
        $queryBuilder = $donationRepository->searchDonations($filters);

        // Pagination : 10 dons par page.
        $donations = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('donation/adminDonation.html.twig', [
            'donations' => $donations,
            'filterForm' => $filterForm->createView(),
            'totalValidatedAmount' => $totalValidatedAmount,
        ]);
    }

    /**
     * Affiche le reçu fiscal PDF d'un don.
     *
     * Le fichier est affiché directement dans le navigateur.
     */
    #[Route('/admin/donation/{id}/pdf', name: 'admin_donation_pdf')]
    public function showDonationPdf(Donation $donation): Response
    {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $pdfPath = $donation->getReceiptPdfPath();

        if (!$pdfPath || !file_exists($pdfPath)) {
            throw $this->createNotFoundException(
                'Le reçu fiscal PDF est introuvable.'
            );
        }

        return $this->file(
            new File($pdfPath),
            'recu-fiscal-' . $donation->getId() . '.pdf',
            ResponseHeaderBag::DISPOSITION_INLINE
        );
    }

    /**
     * Télécharge les reçus fiscaux PDF dans un fichier ZIP.
     *
     * Les reçus téléchargés respectent les filtres appliqués
     * sur la liste des dons dans l'administration.
     */
    #[Route('/admin/donation/telecharger-pdfs', name: 'admin_donation_download_pdfs')]
    public function downloadDonationPdfs(
        Request $request,
        DonationRepository $donationRepository
    ): BinaryFileResponse {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Récupération des filtres envoyés depuis le formulaire d'administration.
        $filters = $request->query->all('donation_filter');

        $donations = $donationRepository
            ->searchDonations($filters)
            ->getQuery()
            ->getResult();

        // Le ZIP est généré à la demande pour éviter de conserver des archives
        // de reçus fiscaux sur le disque après chaque export admin.
        // Création d'un fichier ZIP temporaire.
        $zipPath = sys_get_temp_dir()
            . '/recus-fiscaux-'
            . date('Y-m-d-H-i-s')
            . '.zip';

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException(
                'Impossible de créer le fichier ZIP.'
            );
        }

        // Ajout de chaque reçu fiscal existant dans le ZIP.
        foreach ($donations as $donation) {
            $pdfPath = $donation->getReceiptPdfPath();

            if ($pdfPath && file_exists($pdfPath)) {
                $zip->addFile($pdfPath, basename($pdfPath));
            }
        }

        $zip->close();

        // Symfony prend en charge l'envoi du fichier temporaire en pièce jointe.
        return $this->file(
            $zipPath,
            'recus-fiscaux.zip',
            ResponseHeaderBag::DISPOSITION_ATTACHMENT
        );
    }
}
