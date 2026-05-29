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

final class UserController extends AbstractController
{
    #[Route('/user/detail/{id}', name: 'user_detail_id', methods: ['GET'])]
    public function userDetail(
        User $user
    ): Response {

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }
        if (
            $currentUser->getId() !== $user->getId()
        ) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas accéder à ce profil.'
            );
        }

        return $this->render('user/userDetailById.html.twig', [
            'userById' => $user,
            'editMode'=>false,
        ]);
    }

    #[Route('/user/profile/edit', name: 'user_profile_edit', methods: ['GET', 'POST'])]
    public function editProfile(
        Request                $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a bien été modifié.');

            return $this->redirectToRoute('user_detail_id', [
                'id' => $user->getId(),
            ]);
        }

        return $this->render('user/userDetailById.html.twig', [
            'userById' => $user,
            'formProfile' => $form->createView(),
            'editMode' => true,
        ]);
    }

    #[Route('/user/donation/{id}/pdf', name: 'user_donation_pdf', methods: ['GET'])]
    public function donationPdf(Donation $donation): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (
            $donation->getUser() !== $this->getUser()
        ) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas accéder à ce pdf.'
            );
        }

        $pdfPath = $donation->getReceiptPdfPath();

        if (!$pdfPath || !file_exists($pdfPath)) {
            throw $this->createNotFoundException('PDF introuvable.');
        }

        return $this->file(
            $pdfPath,
            basename($pdfPath),
            ResponseHeaderBag::DISPOSITION_INLINE
        );
    }

    #[Route('/user/precommande/{id}/pdf', name: 'user_preOrder_pdf', methods: ['GET'])]
    public function preOrderPdf(PreOrder $preOrder): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (
            $preOrder->getUser() !== $this->getUser()
        ) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas accéder à ce pdf.'
            );
        }

        $pdfPath = $preOrder->getInvoicePdfPath();

        if (!$pdfPath || !file_exists($pdfPath)) {
            throw $this->createNotFoundException('PDF introuvable.');
        }

        return $this->file(
            $pdfPath,
            basename($pdfPath),
            ResponseHeaderBag::DISPOSITION_INLINE
        );
    }
}
