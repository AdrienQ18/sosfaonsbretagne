<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Form\AlertType;
use App\Repository\AlertRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        MailerInterface $mailer
    ): Response {
        $alert = new Alert();
        $form = $this->createForm(AlertType::class, $alert);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //Enregistrement en base de donnée
            $entityManager->persist($alert);
            $entityManager->flush();

            //Envoi mail a admin
            $this->sendNotificationEmail($mailer, $alert);

            $this->addFlash('success', 'Votre signalement a bien été pris en compte ! Nous vous recontacterons si nécessaire.');

            return $this->redirectToRoute('main_alert');
        }
        return $this->render('alert/pdf/alert.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function sendNotificationEmail(MailerInterface $mailer, Alert $alert): void{
        $adminEmails = ['adrien@quintard.com'];

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
        //vérification que l'utilisateur est un admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        //Récupération de toute les alert depuis la BDD
        $alerts = $alertRepository->findAll();
        //Afficher les alerts
        return $this->render('alert/pdf/adminAlert.html.twig', [
            'alerts' => $alerts,
        ]);
    }
}
