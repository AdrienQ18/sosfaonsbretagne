<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'main_home')]
    public function index(): Response
    {
        return $this->render('main/home.html.twig');
    }

    #[Route('/userList', name: 'userList')]
    public function userList(
        UserRepository $userRepository,
    ): Response{
        $users = $userRepository->findAll();
        return $this->render('admin/userList.html.twig', [
            'users' => $users,
        ]);
    }
}


