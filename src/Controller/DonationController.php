<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DonationController extends AbstractController
{
    #[Route('/donation', name: 'donation')]
    public function index(): Response
    {
        return $this->render('donation/donation.html.twig');
    }
}

