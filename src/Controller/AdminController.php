<?php

namespace App\Controller;


use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
final class AdminController extends AbstractController
{

    #[Route('/userList', name: 'userList')]
    public function userList(
        UserRepository $userRepository,
    ): Response{
        $users = $userRepository->findAll();
        return $this->render('admin/userList.html.twig', [
            'users' => $users,
        ]);
    }


    #[Route('/modify/{id}',name: 'modify', methods: ['GET', 'POST'])]
    public function modify(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        int $id
    ): Response{
        $user = $userRepository->find($id);

        $formUser = $this->createForm(UserType::class, $user);
        $formUser->handleRequest($request);
        if ($formUser->isSubmitted() && $formUser->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Le role à bien été mis à jour');
            return $this->redirectToRoute('admin_userList');
        }
        return $this->render('roles/modifyUser.html.twig', [
            'formUser' => $formUser->createView(),
        ]);
    }
}


