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
    #[Route('/mon-profil', name: 'user_profile', methods: ['GET'])]
    public function profile(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $response = $this->render('user/userDetailById.html.twig', [
            'userById' => $user,
            'editMode' => false,
        ]);

        return $this->disablePrivateCache($response);
    }

    #[Route('/mon-profil/modifier', name: 'user_profile_edit', methods: ['GET', 'POST'])]
    public function editProfile(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

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

        return $this->disablePrivateCache($response);
    }

    #[Route('/mon-profil/don/{id}/pdf', name: 'user_donation_pdf', methods: ['GET'])]
    public function donationPdf(Donation $donation): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

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

        return $this->disablePrivateCache($response);
    }

    #[Route('/mon-profil/precommande/{id}/pdf', name: 'user_preOrder_pdf', methods: ['GET'])]
    public function preOrderPdf(PreOrder $preOrder): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

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

        return $this->disablePrivateCache($response);
    }

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
