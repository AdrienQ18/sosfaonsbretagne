<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Entity\User;
use App\Enum\DonationStatus;
use App\Enum\DonorType;
use App\Form\DonationFilterType;
use App\Form\DonationType;
use App\Repository\DonationRepository;
use App\Service\ServiceHelloAsso\HelloAssoService;
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

final class DonationController extends AbstractController
{
    #[Route('/donation', name: 'donation', methods: ['GET', 'POST'])]
    public function indexDonation(
        Request                $request,
        EntityManagerInterface $entityManager,
        HelloAssoService       $helloAssoService,
        LoggerInterface        $logger,
    ): Response
    {
        $donation = new Donation();
        $donation->setDonorType(DonorType::PARTICULIER);

        $user = $this->getUser();
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
            $donation->setDonationDate(new \DateTime());
            $donation->setStatus(DonationStatus::DONATION_PASSEE);

            $entityManager->persist($donation);
            $entityManager->flush();

            $logger->info('helloasso.donation.created', [
                'donation_id' => $donation->getId(),
                'amount' => (string)$donation->getAmount(),
                'email_hash' => hash('sha256', mb_strtolower(trim((string)$donation->getEmail()))),
            ]);

            try {
                $checkout = $helloAssoService->createDonationCheckout($donation);

                return $this->redirect($checkout['redirectUrl']);
            } catch (\Throwable $e) {
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

    #[Route('/donation/valider/{id}', name: 'donation_success', methods: ['GET'])]
    public function success(Donation $donation, Request $request, LoggerInterface $logger): Response
    {
        $logger->info('helloasso.return_url.hit', [
            'donation_id' => $donation->getId(),
            'query' => $request->query->all(),
        ]);

        $this->addFlash('success', 'Paiement en cours de validation. Votre reçu fiscal sera envoyer par email.');
        return $this->redirectToRoute('donation');

    }

    #[Route('/donation/annuler/{id}', name: 'donation_cancel', methods: ['GET'])]
    public function cancel(Donation $donation, EntityManagerInterface $entityManager): Response
    {
        $donation->setStatus(DonationStatus::DONATION_REFUSEE);
        $entityManager->flush();

        $this->addFlash('error', 'Paiement annulé.');
        return $this->redirectToRoute('donation');
    }

    #[Route('/admin/donation', name: 'admin_donation')]
    public function indexAdminDonation(
        DonationRepository $donationRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
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
            throw $this->createNotFoundException('Le reÃ§u fiscal PDF est introuvable.');
        }

        return $this->file(
            new File($pdfPath),
            'recu-fiscal-' . $donation->getId() . '.pdf',
            ResponseHeaderBag::DISPOSITION_INLINE
        );
    }

    #[Route('/admin/donation/telecharger-pdfs', name: 'admin_donation_download_pdfs')]
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
            throw new \RuntimeException('Impossible de crÃ©er le fichier ZIP.');
        }

        foreach ($donations as $donation) {
            $pdfPath = $donation->getReceiptPdfPath();

            if ($pdfPath && file_exists($pdfPath)) {
                $zip->addFile($pdfPath, basename($pdfPath));
            }
        }

        $zip->close();

        return $this->file(
            $zipPath,
            'recus-fiscaux.zip',
            ResponseHeaderBag::DISPOSITION_ATTACHMENT
        );
    }
}
