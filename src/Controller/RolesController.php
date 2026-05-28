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

final class RolesController extends AbstractController
{
    #[Route('/admin/roles', name: 'admin_roles', methods: ['GET', 'POST'])]
    #[Route('/admin/roles/update/{id}', name: 'admin_roles_update', methods: ['GET', 'POST'])]
    public function index(
        RoleRepository         $roleRepository,
        EntityManagerInterface $entityManager,
        Request                $request,
        ?int                   $id = null,
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($id !== null) {
            $role = $roleRepository->find($id);
            if (!$role) {
                throw $this->createNotFoundException('La disponibilité n\'existe pas');
            }
        } else {
            $role = new Role();
        }
        $roleList = $roleRepository->findAll();
        $formRole = $this->createForm(RoleType::class, $role);
        $formRole->handleRequest($request);

        if ($formRole->isSubmitted() && $formRole->isValid()) {
            if ($id === null) {
                $entityManager->persist($role);
            }
            $entityManager->flush();
            $this->addFlash('success', $id === null ? 'Rôle ajouté avec succès.' : 'Rôle modifié avec succès.');
            return $this->redirectToRoute('admin_roles');
        }
        return $this->render('roles/adminRole.html.twig', [
            'roleList' => $roleList,
            'formRole' => $formRole->createView(),
            'isEdit' => $id !== null,
        ]);
    }

    #[Route('/admin/roles/delete/{id}', name: 'admin_roles_delete', methods: ['POST'])]
    public function deleteRole(
        int                    $id,
        RoleRepository         $roleRepository,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $role = $roleRepository->find($id);
        if (!$role) {
            throw $this->createNotFoundException('La disponibilité n\'existe pas');
        }
        $entityManager->remove($role);
        $entityManager->flush();
        $this->addFlash('success', 'Votre rôle a bien été supprimé.');
        return $this->redirectToRoute('admin_roles');
    }

}
