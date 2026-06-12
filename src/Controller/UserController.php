<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Entity\PreOrder;
use App\Entity\User;
use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion du profil utilisateur.
 *
 * Il permet à un utilisateur connecté :
 * - de consulter son profil ;
 * - de modifier ses informations personnelles ;
 * - de consulter ses reçus fiscaux ;
 * - de consulter ses factures de précommande.
 */
final class UserController extends AbstractController
{
    /**
     * Affiche le profil de l'utilisateur connecté.
     */
    #[Route('/mon-profil', name: 'user_profile', methods: ['GET'])]
    public function profile(): Response
    {
        // Sécurité : accès réservé aux utilisateurs connectés.
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $response = $this->render('user/userDetailById.html.twig', [
            'userById' => $user,
            'editMode' => false,
        ]);

        // Empêche la mise en cache des données personnelles.
        return $this->disablePrivateCache($response);
    }

    /**
     * Affiche et traite le formulaire de modification du profil.
     */
    #[Route('/mon-profil/modifier', name: 'user_profile_edit', methods: ['GET', 'POST'])]
    public function editProfile(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Sécurité : accès réservé aux utilisateurs connectés.
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        // Formulaire lié directement à l'utilisateur connecté.
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        // Si le formulaire est valide, Doctrine enregistre les modifications.
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a bien été modifié.');

            return $this->redirectToRoute('user_profile');
        }

        $response = $this->render('user/userDetailById.html.twig', [
            'userById' => $user,
            'formProfile' => $form->createView(),
            'editMode' => true,
        ]);

        // Empêche la mise en cache des données personnelles.
        return $this->disablePrivateCache($response);
    }

    /**
     * Affiche le reçu fiscal PDF d'un don appartenant à l'utilisateur connecté.
     */
    #[Route('/mon-profil/don/{id}/pdf', name: 'user_donation_pdf', methods: ['GET'])]
    public function donationPdf(Donation $donation): Response
    {
        // Sécurité : accès réservé aux utilisateurs connectés.
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Vérifie que le don demandé appartient bien à l'utilisateur connecté.
        if ($donation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas accéder à ce PDF.'
            );
        }

        $pdfPath = $donation->getReceiptPdfPath();

        if (!$pdfPath || !file_exists($pdfPath)) {
            throw $this->createNotFoundException('PDF introuvable.');
        }

        $response = $this->file(
            $pdfPath,
            basename($pdfPath),
            ResponseHeaderBag::DISPOSITION_INLINE
        );

        // Empêche le cache navigateur sur les documents personnels.
        return $this->disablePrivateCache($response);
    }

    /**
     * Affiche la facture PDF d'une précommande appartenant à l'utilisateur connecté.
     */
    #[Route('/mon-profil/precommande/{id}/pdf', name: 'user_preOrder_pdf', methods: ['GET'])]
    public function preOrderPdf(PreOrder $preOrder): Response
    {
        // Sécurité : accès réservé aux utilisateurs connectés.
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Vérifie que la précommande demandée appartient bien à l'utilisateur connecté.
        if ($preOrder->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas accéder à ce PDF.'
            );
        }

        $pdfPath = $preOrder->getInvoicePdfPath();

        if (!$pdfPath || !file_exists($pdfPath)) {
            throw $this->createNotFoundException('PDF introuvable.');
        }

        $response = $this->file(
            $pdfPath,
            basename($pdfPath),
            ResponseHeaderBag::DISPOSITION_INLINE
        );

        // Empêche le cache navigateur sur les documents personnels.
        return $this->disablePrivateCache($response);
    }

    /**
     * Désactive le cache navigateur pour les pages contenant
     * des données personnelles ou des documents privés.
     */
    private function disablePrivateCache(Response $response): Response
    {
        $response->headers->set(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, max-age=0'
        );

        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
