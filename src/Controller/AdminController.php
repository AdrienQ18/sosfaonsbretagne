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

/**
 * Contrôleur d'administration.
 *
 * Ce contrôleur regroupe les actions réservées aux administrateurs,
 * notamment la gestion et la modification des utilisateurs.
 */
#[Route('/admin', name: 'admin_')]
final class AdminController extends AbstractController
{
    /**
     * Affiche la liste des utilisateurs avec un système de filtre et de pagination.
     *
     * Cette page est uniquement accessible aux utilisateurs ayant le rôle ROLE_ADMIN.
     */
    #[Route('/liste-utilisateurs', name: 'userList')]
    public function userList(
        Request $request,
        UserRepository $userRepository,
        PaginatorInterface $paginator
    ): Response {
        // Sécurité : bloque l'accès si l'utilisateur connecté n'est pas administrateur.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Création du formulaire de filtres en méthode GET pour conserver les paramètres dans l'URL.
        $form = $this->createForm(UserFilterType::class, null, [
            'method' => 'GET',
        ]);

        // Analyse la requête HTTP et remplit le formulaire avec les filtres envoyés.
        $form->handleRequest($request);

        // Récupère une requête Doctrine filtrée selon les critères du formulaire.
        $query = $userRepository->findByFiltersQuery($form->getData() ?? []);

        // Pagination des résultats : 10 utilisateurs affichés par page.
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

    /**
     * Permet de modifier les informations ou le rôle d'un utilisateur.
     *
     * Le formulaire est affiché en GET et traité en POST.
     */
    #[Route('/modifier-utilisateur/{id}', name: 'modify', methods: ['GET', 'POST'])]
    public function modify(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        int $id
    ): Response {
        // Recherche de l'utilisateur à modifier à partir de son identifiant.
        $user = $userRepository->find($id);

        // Création du formulaire lié à l'utilisateur récupéré.
        $formUser = $this->createForm(UserType::class, $user);

        // Traitement des données envoyées par le formulaire.
        $formUser->handleRequest($request);

        // Si le formulaire est soumis et valide, les modifications sont enregistrées.
        if ($formUser->isSubmitted() && $formUser->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            // Message de confirmation affiché après la redirection.
            $this->addFlash('success', 'Le rôle a bien été mis à jour');

            return $this->redirectToRoute('admin_userList');
        }

        return $this->render('roles/modifyUser.html.twig', [
            'formUser' => $formUser->createView(),
        ]);
    }
}
