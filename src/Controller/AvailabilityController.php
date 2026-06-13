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

/**
 * Contrôleur de gestion des disponibilités.
 *
 * Permet aux administrateurs :
 * - d'ajouter une disponibilité ;
 * - de modifier une disponibilité existante ;
 * - de supprimer une disponibilité.
 */
final class AvailabilityController extends AbstractController
{
    /**
     * Affiche la liste des disponibilités et gère
     * l'ajout ou la modification d'une disponibilité.
     *
     * Si un identifiant est présent dans l'URL,
     * le formulaire est pré-rempli pour l'édition.
     */
    #[Route('/admin/disponibilite', name: 'admin_availability', methods: ['GET', 'POST'])]
    #[Route('/admin/disponibilite/modifier/{id}', name: 'admin_availability_update', methods: ['GET', 'POST'])]
    public function index(
        AvailabilityRepository $availabilityRepository,
        EntityManagerInterface $entityManager,
        Request $request,
        ?int $id = null,
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Mode modification : récupération de la disponibilité existante.
        if ($id !== null) {
            $availability = $availabilityRepository->find($id);

            if (!$availability) {
                throw $this->createNotFoundException(
                    'La disponibilité n\'existe pas.'
                );
            }
        } else {
            // Mode création : nouvelle disponibilité.
            $availability = new Availability();
        }

        // Récupération de toutes les disponibilités pour l'affichage.
        $availabilityList = $availabilityRepository->findAll();

        // Création et traitement du formulaire.
        $formAvailability = $this->createForm(
            AvailabilityType::class,
            $availability
        );

        $formAvailability->handleRequest($request);

        // Enregistrement des données si le formulaire est valide.
        if ($formAvailability->isSubmitted() && $formAvailability->isValid()) {

            // Persist uniquement lors de la création.
            // En édition, l'entité est déjà suivie par Doctrine.
            if ($id === null) {
                $entityManager->persist($availability);
            }

            $entityManager->flush();

            $this->addFlash(
                'success',
                $id === null
                    ? 'Disponibilité ajoutée avec succès.'
                    : 'Disponibilité modifiée avec succès.'
            );

            return $this->redirectToRoute('admin_availability');
        }

        return $this->render('availability/adminAvailability.html.twig', [
            'availabilityList' => $availabilityList,
            'formAvailability' => $formAvailability->createView(),

            // Permet d'adapter l'affichage Twig selon le mode création ou édition.
            'isEdit' => $id !== null,
        ]);
    }

    /**
     * Supprime une disponibilité.
     *
     * Une vérification CSRF est effectuée avant la suppression.
     */
    #[Route('/admin/disponibilite/supprimer/{id}', name: 'admin_availability_delete', methods: ['POST'])]
    public function deleteAvailability(
        int $id,
        Request $request,
        AvailabilityRepository $availabilityRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Recherche de la disponibilité à supprimer.
        $availability = $availabilityRepository->find($id);

        if (!$availability) {
            throw $this->createNotFoundException(
                'La disponibilité n\'existe pas.'
            );
        }

        // Vérification du token CSRF pour éviter les suppressions frauduleuses.
        if (!$this->isCsrfTokenValid(
            'delete_availability_' . $availability->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        // Suppression de la disponibilité.
        $entityManager->remove($availability);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'La disponibilité a bien été supprimée.'
        );

        return $this->redirectToRoute('admin_availability');
    }
}
