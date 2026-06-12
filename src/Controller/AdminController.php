<?php

namespace App\Controller;


use App\Form\UserFilterType;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
final class AdminController extends AbstractController
{

    #[Route('/liste-utilisateurs', name: 'userList')]
    public function userList(
        Request $request,
        UserRepository $userRepository,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(UserFilterType::class, null, [
            'method' => 'GET',
        ]);

        $form->handleRequest($request);

        $query = $userRepository->findByFiltersQuery($form->getData() ?? []);

        $users = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/userList.html.twig', [
            'users' => $users,
            'filterForm' => $form->createView(),
        ]);
    }


    #[Route('/modifier-utilisateur/{id}',name: 'modify', methods: ['GET', 'POST'])]
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


