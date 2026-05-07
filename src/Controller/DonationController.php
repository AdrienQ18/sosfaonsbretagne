<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Form\DonationType;
use App\Repository\DonationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\HelloAssoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DonationController extends AbstractController
{
    #[Route('/donation', name: 'donation', methods: ['GET', 'POST'])]
    public function indexDonation(
        Request                $request,
        EntityManagerInterface $entityManager,
        HelloAssoService        $helloAssoService
    ): Response
    {
        $newDonation = new Donation();
        $formDonation = $this->createForm(DonationType::class, $newDonation);
        $formDonation->handleRequest($request);

        if ($formDonation->isSubmitted() && $formDonation->isValid()) {
            $newDonation->setDonationDate(new \DateTime());

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
        $donation = $donationRepository->findAll();
        return $this->render('donation/adminDonation.html.twig', [
            'donations' => $donation
        ]);
    }

    #[Route('/donation/success', name: 'donation_success')]
    public function success(): Response
    {
        $this->addFlash('success', 'Paiement terminé. Votre don est en cours de validation.');

        return $this->redirectToRoute('donation');
    }

    #[Route('/donation/cancel', name: 'donation_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('error', 'Paiement annulé.');

        return $this->redirectToRoute('donation');
    }
}

