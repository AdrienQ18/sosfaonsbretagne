<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Enum\AlertStatus;
use App\Form\AlertFilterType;
use App\Form\AlertType;
use App\Repository\AlertRepository;
use App\Service\Utils\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class AlertController extends AbstractController
{
    #[Route('/alert', name: 'main_alert', methods: ['GET', 'POST'])]
    public function alert(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        FileUploader $fileUploader,
    ): Response {
        $alert = new Alert();
        $alert->setCreationDate(new \DateTime());

        if ($this->getUser()) {
            $alert->setUser($this->getUser());
        }

        $form = $this->createForm(AlertType::class, $alert);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $form->get('image')->getData();

            if ($file) {
                $alert->setImage(
                    $fileUploader->upload($file, 'alert', $alert->getImage())
                );
            }

            $entityManager->persist($alert);
            $entityManager->flush();

            $this->sendNotificationEmail($mailer, $alert);

            $this->addFlash(
                'success',
                'Votre signalement a bien été pris en compte ! Nous vous recontacterons si nécessaire.'
            );

            return $this->redirectToRoute('main_alert');
        }

        return $this->render('alert/alert.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function sendNotificationEmail(
        MailerInterface $mailer,
        Alert $alert
    ): void {
        $adminEmails = [
            'contact@sosfaonsbretagne.fr',
        ];

        foreach ($adminEmails as $adminEmail) {
            $email = (new Email())
                ->from('contact@sosfaonsbretagne.fr')
                ->to($adminEmail)
                ->subject('[Signalement] ' . $alert->getType())
                ->html($this->renderView('alert/email/alert_receipt.html.twig', [
                    'alert' => $alert,
                ]));

            $mailer->send($email);
        }
    }

    #[Route('/admin/alert', name: 'admin_alert')]
    public function indexAdminAlert(
        AlertRepository $alertRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(AlertFilterType::class, null, [
            'method' => 'GET',
        ]);

        $form->handleRequest($request);

        $query = $alertRepository->findByFiltersQuery($form->getData() ?? []);

        $alerts = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('alert/adminAlert.html.twig', [
            'alerts' => $alerts,
            'filterForm' => $form->createView(),
        ]);
    }

    #[Route('/admin/alert/{id}', name: 'admin_alert_show', methods: ['GET', 'POST'])]
    public function showAdminAlert(
        Alert $alert,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'admin_alert_update_' . $alert->getId(),
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException('Token CSRF invalide.');
            }

            $interventionDate = $request->request->get('interventionDate');
            $status = $request->request->get('status');

            if ($interventionDate) {
                $alert->setInterventionDate(new \DateTime($interventionDate));
            }

            if ($status) {
                $alert->setStatus(AlertStatus::from($status));
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le signalement a bien été mis à jour.');

            return $this->redirectToRoute('admin_alert_show', [
                'id' => $alert->getId(),
            ]);
        }

        return $this->render('alert/adminAlertShow.html.twig', [
            'alert' => $alert,
        ]);
    }
}
