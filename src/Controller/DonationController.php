<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use App\Enum\DonorType;
use App\Form\DonationFilterType;
use App\Form\DonationType;
use App\Repository\DonationRepository;

use App\Service\ServiceDonation\DonationPdfService;
use App\Service\ServiceHelloAsso\HelloAssoService;
use App\Service\ServiceHelloAsso\HelloAssoWebhookService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use ZipArchive;

final class DonationController extends AbstractController
{
    #[Route('/donation', name: 'donation', methods: ['GET', 'POST'])]
    public function indexDonation(
        Request                $request,
        EntityManagerInterface $entityManager,
        HelloAssoService       $helloAssoService
    ): Response
    {
        $newDonation = new Donation();
        $newDonation->setDonorType(DonorType::PARTICULIER);

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
            $newDonation->setDonationDate(new \DateTime());
            $newDonation->setStatus(DonationStatus::DONATION_PASSEE);

            $entityManager->persist($newDonation);
            $entityManager->flush();

            try {
                $redirectUrl = $helloAssoService->createDonationCheckout($newDonation);

                return $this->redirect($redirectUrl);
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Impossible de créer le paiement.');

                return $this->redirectToRoute('donation');
            }
        }

        return $this->render('donation/donation.html.twig', [
            'formDonation' => $formDonation->createView(),
        ]);
    }

    #[Route('/admin/donation', name: 'admin_donation')]
    public function indexAdminDonation(
        DonationRepository $donationRepository,
        PaginatorInterface $paginator,
        Request            $request
    ): Response
    {
        $filterForm = $this->createForm(DonationFilterType::class, null, [
            'method' => 'GET',
        ]);
        $filterForm->handleRequest($request);
        $filters = $filterForm->getData() ?? [];
        $totalValidatedAmount = $donationRepository->getValidatedTotalAmount($filters);
        $queryBuilder = $donationRepository->searchDonations($filters);
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

    #[Route('/admin/donation/download-pdfs', name: 'admin_donation_download_pdfs')]
    public function downloadDonationPdfs(
        Request $request,
        DonationRepository $donationRepository
    ): BinaryFileResponse {
        $filters = $request->query->all('donation_filter');

        $donations = $donationRepository
            ->searchDonations($filters)
            ->getQuery()
            ->getResult();

        $zipPath = sys_get_temp_dir() . '/recus-fiscaux-' . date('Y-m-d-H-i-s') . '.zip';

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Impossible de créer le fichier ZIP.');
        }

        foreach ($donations as $donation) {
            $pdfPath = $donation->getReceiptPdfPath();

            if ($pdfPath && file_exists($pdfPath)) {
                $zip->addFile(
                    $pdfPath,
                    basename($pdfPath)
                );
            }
        }

        $zip->close();

        return $this->file(
            $zipPath,
            'recus-fiscaux.zip',
            ResponseHeaderBag::DISPOSITION_ATTACHMENT
        );
    }
    #[Route('/donation/success/{id}', name: 'donation_success')]
    public function success(): Response
    {
        $this->addFlash('success', 'Paiement terminé. Votre don est en cours de validation.');
        return $this->redirectToRoute('donation');    }

    #[Route('/donation/cancel/{id}', name: 'donation_cancel')]
    public function cancel(Donation $donation, EntityManagerInterface $entityManager): Response
    {
        $donation->setStatus(DonationStatus::DONATION_REFUSEE);
        $entityManager->flush();
        $this->addFlash('error', 'Paiement annulé.');
        return $this->redirectToRoute('donation');    }

    #[Route('/helloasso/webhook', name: 'helloasso_webhook', methods: ['POST'])]
    public function helloAssoWebhook(
        Request                 $request,
        HelloAssoWebhookService $helloAssoWebhookService
    ): JsonResponse
    {
        $receivedSecret = $request->query->get('secret');
        $expectedSecret = $_ENV['HELLOASSO_WEBHOOK_SECRET'];
        if (!$receivedSecret || !hash_equals($expectedSecret, $receivedSecret)) {
            return new JsonResponse([
                'error' => 'Notification non autorisée.',
            ], 403);
        }
        $payload = json_decode($request->getContent(), true);
//        file_put_contents(
//            $this->getParameter('kernel.project_dir') . '/var/helloasso-debug.json',
//            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
//        );
        if (!$payload) {
            return new JsonResponse([
                'error' => 'Le contenu reçu est invalide ou vide.',
            ], 400);
        }

        $result = $helloAssoWebhookService->handle($payload);

        return new JsonResponse($result, $result['status']);
    }

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
