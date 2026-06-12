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

final class AvailabilityController extends AbstractController
{
    #[Route('/admin/disponibilite', name: 'admin_availability', methods: ['GET', 'POST'])]
    #[Route('/admin/disponibilite/modifier/{id}', name: 'admin_availability_update', methods: ['GET', 'POST'])]
    public function index(
        AvailabilityRepository $availabilityRepository,
        EntityManagerInterface $entityManager,
        Request                $request,
        ?int                   $id = null,
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($id !== null) {
            $availability = $availabilityRepository->find($id);
            if (!$availability) {
                throw $this->createNotFoundException('La disponibilité n\'existe pas');
            }
        } else {
            $availability = new Availability();
        }
        $availabilityList = $availabilityRepository->findAll();
        $formAvailability = $this->createForm(AvailabilityType::class, $availability);
        $formAvailability->handleRequest($request);

        if ($formAvailability->isSubmitted() && $formAvailability->isValid()) {
            if ($id === null) {
                $entityManager->persist($availability);
            }
            $entityManager->flush();
            $this->addFlash('success', $id === null ? 'disponibilité ajouté avec succès.' : 'disponibilité modifié avec succès.');
            return $this->redirectToRoute('admin_availability');
        }
        return $this->render('availability/adminAvailability.html.twig', [
            'availabilityList' => $availabilityList,
            'formAvailability' => $formAvailability->createView(),
            'isEdit' => $id !== null,
        ]);
    }

    #[Route('/admin/disponibilite/supprimer/{id}', name: 'admin_availability_delete', methods: ['POST'])]
    public function deleteAvailability(
        int $id,
        Request $request,
        AvailabilityRepository $availabilityRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $availability = $availabilityRepository->find($id);

        if (!$availability) {
            throw $this->createNotFoundException('La disponibilité n\'existe pas.');
        }

        if (!$this->isCsrfTokenValid(
            'delete_availability_' . $availability->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $entityManager->remove($availability);
        $entityManager->flush();

        $this->addFlash('success', 'Votre disponibilité a bien été supprimée.');

        return $this->redirectToRoute('admin_availability');
    }
}
