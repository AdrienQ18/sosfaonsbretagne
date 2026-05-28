<?php

namespace App\Controller;

use App\Entity\Role;
use App\Form\RoleType;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/roles')]
final class RolesController extends AbstractController
{
    #[Route(name: 'admin_roles', methods: ['GET', 'POST'])]
    public function index(
        RoleRepository         $roleRepository,
        EntityManagerInterface $entityManager,
        Request                $request,
    ): Response
    {
        $roleList = $roleRepository->findAll();
        $role = new Role();
        $formRole = $this->createForm(RoleType::class, $role);
        $formRole->handleRequest($request);

        if ($formRole->isSubmitted() && $formRole->isValid()) {
            $entityManager->persist($role);
            $entityManager->flush();
            return $this->redirectToRoute('admin_roles');
        }
        return $this->render('roles/adminRole.html.twig', [
            'roleList' => $roleList,
            'formRole' => $formRole->createView(),
        ]);
    }
}
