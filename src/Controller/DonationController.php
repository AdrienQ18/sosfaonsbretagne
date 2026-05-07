<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use App\Form\DonationType;
use App\Repository\DonationRepository;
use App\Service\HelloAssoService;
use App\Service\HelloAssoWebhookService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

        $formDonation = $this->createForm(DonationType::class, $newDonation);
        $formDonation->handleRequest($request);

        if ($formDonation->isSubmitted() && $formDonation->isValid()) {
            $newDonation->setDonationDate(new \DateTime());
            $newDonation->setStatus(DonationStatus::DONATION_PASSEE);

            $entityManager->persist($newDonation);
            $entityManager->flush();

            $redirectUrl = $helloAssoService->createDonationCheckout($newDonation);

            return $this->redirect($redirectUrl);
        }

        return $this->render('donation/donation.html.twig', [
            'formDonation' => $formDonation->createView(),
        ]);
    }

    #[Route('/admin/donation', name: 'admin_donation')]
    public function indexAdminDonation(DonationRepository $donationRepository): Response
    {
        return $this->render('donation/adminDonation.html.twig', [
            'donations' => $donationRepository->findAll(),
        ]);
    }

    #[Route('/donation/success/{id}', name: 'donation_success')]
    public function success(): Response
    {
        $this->addFlash('success', 'Paiement terminé. Votre don est en cours de validation.');

        return $this->redirectToRoute('donation');
    }

    #[Route('/donation/cancel/{id}', name: 'donation_cancel')]
    public function cancel(Donation $donation, EntityManagerInterface $entityManager): Response
    {
        $donation->setStatus(DonationStatus::DONATION_REFUSEE);
        $entityManager->flush();

        $this->addFlash('error', 'Paiement annulé.');

        return $this->redirectToRoute('donation');
    }

    #[Route('/helloasso/webhook', name: 'helloasso_webhook', methods: ['POST'])]
    public function helloAssoWebhook(
        Request $request,
        HelloAssoWebhookService $helloAssoWebhookService
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        file_put_contents(
            $this->getParameter('kernel.project_dir') . '/var/helloasso-webhook.json',
            $request->getContent() . PHP_EOL,
            FILE_APPEND
        );

        if (!$payload) {
            return new JsonResponse([
                'error' => 'Le contenu reçu est invalide ou vide.',
            ], 400);
        }

        $result = $helloAssoWebhookService->handle($payload);

        return new JsonResponse($result, $result['status']);
    }
}
