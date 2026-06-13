<?php

namespace App\Controller;

use App\Entity\Role;
use App\Form\RoleType;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion des rôles.
 *
 * Permet aux administrateurs :
 * - d'ajouter un rôle ;
 * - de modifier un rôle existant ;
 * - de supprimer un rôle.
 */
final class RolesController extends AbstractController
{
    /**
     * Affiche la liste des rôles et gère
     * l'ajout ou la modification d'un rôle.
     *
     * Si un identifiant est présent dans l'URL,
     * le formulaire est pré-rempli pour l'édition.
     */
    #[Route('/admin/roles', name: 'admin_roles', methods: ['GET', 'POST'])]
    #[Route('/admin/roles/modifier/{id}', name: 'admin_roles_update', methods: ['GET', 'POST'])]
    public function index(
        RoleRepository $roleRepository,
        EntityManagerInterface $entityManager,
        Request $request,
        ?int $id = null,
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Mode modification : récupération du rôle existant.
        if ($id !== null) {
            $role = $roleRepository->find($id);

            if (!$role) {
                throw $this->createNotFoundException(
                    'Le rôle n\'existe pas.'
                );
            }
        } else {
            // Mode création : nouveau rôle.
            $role = new Role();
        }

        // Récupération de tous les rôles pour l'affichage.
        $roleList = $roleRepository->findAll();

        // Création et traitement du formulaire.
        $formRole = $this->createForm(RoleType::class, $role);
        $formRole->handleRequest($request);

        if ($formRole->isSubmitted() && $formRole->isValid()) {

            // Persist uniquement lors de la création.
            // En édition, l'entité est déjà suivie par Doctrine.
            if ($id === null) {
                $entityManager->persist($role);
            }

            $entityManager->flush();

            $this->addFlash(
                'success',
                $id === null
                    ? 'Rôle ajouté avec succès.'
                    : 'Rôle modifié avec succès.'
            );

            return $this->redirectToRoute('admin_roles');
        }

        return $this->render('roles/adminRole.html.twig', [
            'roleList' => $roleList,
            'formRole' => $formRole->createView(),

            // Permet d'adapter l'affichage Twig selon le mode création ou édition.
            'isEdit' => $id !== null,
        ]);
    }

    /**
     * Supprime un rôle.
     *
     * Une vérification CSRF est effectuée avant la suppression.
     */
    #[Route('/admin/roles/supprimer/{id}', name: 'admin_roles_delete', methods: ['POST'])]
    public function deleteRole(
        int $id,
        Request $request,
        RoleRepository $roleRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Recherche du rôle à supprimer.
        $role = $roleRepository->find($id);

        if (!$role) {
            throw $this->createNotFoundException(
                'Le rôle n\'existe pas.'
            );
        }

        // Vérification du token CSRF pour éviter les suppressions frauduleuses.
        if (!$this->isCsrfTokenValid(
            'delete_role_' . $role->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Token CSRF invalide.'
            );
        }

        // Suppression du rôle.
        $entityManager->remove($role);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'Le rôle a bien été supprimé.'
        );

        return $this->redirectToRoute('admin_roles');
    }
}
