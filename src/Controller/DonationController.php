<?php

namespace App\Controller;

use App\Entity\Donation;
use App\Entity\User;
use App\Enum\DonationStatus;
use App\Enum\DonorType;
use App\Form\DonationType;
use App\Service\ServiceHelloAsso\HelloAssoService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DonationController extends AbstractController
{
    #[Route('/donation', name: 'donation', methods: ['GET', 'POST'])]
    public function indexDonation(
        Request                $request,
        EntityManagerInterface $entityManager,
        HelloAssoService       $helloAssoService,
        LoggerInterface        $logger,
    ): Response
    {
        $donation = new Donation();
        $donation->setDonorType(DonorType::PARTICULIER);

        $user = $this->getUser();
        if ($user) {
            /** @var User $user */
            $donation->setUser($user);
            $donation->setFirstname($user->getFirstname());
            $donation->setLastname($user->getLastname());
            $donation->setEmail($user->getEmail());
            $donation->setCity($user->getCity());
            $donation->setAddress($user->getAddress());
            $donation->setZipcode($user->getZipcode());
        }

        $form = $this->createForm(DonationType::class, $donation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $donation->setDonationDate(new \DateTime());
            $donation->setStatus(DonationStatus::DONATION_PASSEE);

            $entityManager->persist($donation);
            $entityManager->flush();

            $logger->info('helloasso.donation.created', [
                'donation_id' => $donation->getId(),
                'amount' => (string)$donation->getAmount(),
                'email_hash' => hash('sha256', mb_strtolower(trim((string)$donation->getEmail()))),
            ]);

            try {
                $checkout = $helloAssoService->createDonationCheckout($donation);

                return $this->redirect($checkout['redirectUrl']);
            } catch (\Throwable $e) {
                $entityManager->remove($donation);
                $entityManager->flush();

                $logger->error('helloasso.checkout.create_failed', [
                    'donation_id' => $donation->getId(),
                    'error' => $e->getMessage(),
                ]);

                $this->addFlash('error', 'Impossible de créer le paiement.');
                return $this->redirectToRoute('donation');
            }
        }

        return $this->render('donation/donation.html.twig', [
            'formDonation' => $form->createView(),
        ]);
    }

    #[Route('/donation/valider/{id}', name: 'donation_success', methods: ['GET'])]
    public function success(Donation $donation, Request $request, LoggerInterface $logger): Response
    {
        $logger->info('helloasso.return_url.hit', [
            'donation_id' => $donation->getId(),
            'query' => $request->query->all(),
        ]);

        $this->addFlash('success', 'Paiement en cours de validation. Votre reçu fiscal sera envoyer par email.');
        return $this->redirectToRoute('donation');

    }

    #[Route('/donation/annuler/{id}', name: 'donation_cancel', methods: ['GET'])]
    public function cancel(Donation $donation, EntityManagerInterface $entityManager): Response
    {
        $donation->setStatus(DonationStatus::DONATION_REFUSEE);
        $entityManager->flush();

        $this->addFlash('error', 'Paiement annulé.');
        return $this->redirectToRoute('donation');
    }

}
