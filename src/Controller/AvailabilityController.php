<?php

namespace App\Controller;

use App\Entity\Availability;
use App\Form\AvailabilityType;
use App\Repository\AvailabilityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/availability')]
final class AvailabilityController extends AbstractController
{
    #[Route(name: 'admin_availability', methods: ['GET', 'POST'])]
    public function index(
        AvailabilityRepository $availabilityRepository,
        EntityManagerInterface $entityManager,
        Request                $request,
    ): Response
    {
        $availabilityList = $availabilityRepository->findAll();
        $availability = new Availability();
        $formAvailability = $this->createForm(AvailabilityType::class, $availability);
        $formAvailability->handleRequest($request);

        if ($formAvailability->isSubmitted() && $formAvailability->isValid()) {
            $entityManager->persist($availability);
            $entityManager->flush();
            return $this->redirectToRoute('admin_availability');

        }
        return $this->render('availability/adminAvailability.html.twig', [
            'availabilityList' => $availabilityList,
            'formAvailability' => $formAvailability->createView(),
        ]);
    }
}
