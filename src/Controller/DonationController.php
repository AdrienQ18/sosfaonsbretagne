<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Form\DonationType;
use App\Repository\DonationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DonationController extends AbstractController
{
    #[Route('/donation', name: 'donation')]
    public function indexDonation(): Response
    {
        $formDonation = $this->createForm(DonationType::class, new Donation());
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
}

