<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Enum\AlertStatus;
use App\Form\AlertFilterType;
use App\Form\AlertType;
use App\Repository\AlertRepository;
use App\Service\Signalement\AlertMailerService;
use App\Service\Utils\ImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur lié aux signalements.
 *
 * Il permet :
 * - aux utilisateurs connectés d’envoyer un signalement ;
 * - aux administrateurs de consulter, filtrer et modifier les signalements.
 */
final class AlertController extends AbstractController
{
    /**
     * Affiche et traite le formulaire public de signalement.
     *
     * L’utilisateur doit être connecté pour envoyer un signalement.
     * Une image peut être ajoutée au signalement.
     */
    #[Route('/signalement', name: 'main_alert', methods: ['GET', 'POST'])]
    public function alert(
        Request $request,
        EntityManagerInterface $entityManager,
        AlertMailerService $alertMailerService,
        ImageUploader $imageUploader,
    ): Response {
        $alert = new Alert();

        // Date de création du signalement.
        $alert->setCreationDate(new \DateTime());

        // Si un utilisateur est connecté, on l’associe automatiquement au signalement.
        if ($this->getUser()) {
            $alert->setUser($this->getUser());
        }

        $form = $this->createForm(AlertType::class, $alert);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sécurité : un signalement ne peut être envoyé que par un utilisateur connecté.
            if (!$this->getUser()) {
                $this->addFlash(
                    'warning',
                    'Vous devez être connecté pour envoyer un signalement.'
                );

                return $this->redirectToRoute('app_login');
            }

            /** @var UploadedFile|null $file */
            $file = $form->get('image')->getData();

            // Upload de l’image si un fichier a été envoyé avec le formulaire.
            if ($file) {
                $alert->setImage(
                    $imageUploader->upload($file, 'alert', $alert->getImage())
                );
            }

            // Enregistrement du signalement en base de données.
            $entityManager->persist($alert);
            $entityManager->flush();

            // Envoi d’un email aux administrateurs pour les prévenir du nouveau signalement.
            $alertMailerService->sendAdminNotification($alert);

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

    /**
     * Affiche la liste des signalements côté administration.
     *
     * Les signalements peuvent être filtrés et sont affichés avec une pagination.
     */
    #[Route('/admin/signalement', name: 'admin_alert')]
    public function indexAdminAlert(
        AlertRepository $alertRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Formulaire de filtre en GET pour conserver les critères dans l’URL.
        $form = $this->createForm(AlertFilterType::class, null, [
            'method' => 'GET',
        ]);

        $form->handleRequest($request);

        // Requête Doctrine filtrée selon les critères renseignés.
        $query = $alertRepository->findByFiltersQuery($form->getData() ?? []);

        // Pagination : 9 signalements par page.
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

    /**
     * Affiche le détail d’un signalement et permet sa mise à jour par un administrateur.
     *
     * L’administrateur peut modifier :
     * - la date d’intervention ;
     * - le statut du signalement.
     *
     * Un email est envoyé à l’utilisateur si le statut passe à validé ou refusé.
     */
    #[Route('/admin/signalement/{id}', name: 'admin_alert_show', methods: ['GET', 'POST'])]
    public function showAdminAlert(
        Alert $alert,
        Request $request,
        EntityManagerInterface $entityManager,
        AlertMailerService $alertMailerService
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            // Vérification du token CSRF pour protéger la modification du signalement.
            if (!$this->isCsrfTokenValid(
                'admin_alert_update_' . $alert->getId(),
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException('Token CSRF invalide.');
            }

            // Sauvegarde de l’ancien statut pour savoir s’il a changé après modification.
            $oldStatus = $alert->getStatus();

            $interventionDate = $request->request->get('interventionDate');
            $status = $request->request->get('status');

            // Le champ est optionnel : l'absence de date conserve la valeur
            // actuellement enregistrée sur le signalement.
            // Mise à jour de la date d’intervention si elle est renseignée.
            if ($interventionDate) {
                $alert->setInterventionDate(new \DateTime($interventionDate));
            }

            // Mise à jour du statut à partir de l’énumération AlertStatus.
            if ($status) {
                $alert->setStatus(AlertStatus::from($status));
            }

            $entityManager->flush();

            // Récupération du nouveau statut après modification.
            $newStatus = $alert->getStatus();

            // Envoi d’un email uniquement si le statut a réellement changé.
            if ($oldStatus !== $newStatus) {
                // On notifie uniquement les décisions finales utiles à l'utilisateur.
                if ($newStatus === AlertStatus::SIGNALEMENT_VALIDEE) {
                    $alertMailerService->sendValidatedNotification($alert);
                }

                if ($newStatus === AlertStatus::SIGNALEMENT_REFUSEE) {
                    $alertMailerService->sendRefusedNotification($alert);
                }
            }

            $this->addFlash('success', 'Le signalement a bien été mis à jour.');

            return $this->redirectToRoute('admin_alert');
        }

        return $this->render('alert/adminAlertShow.html.twig', [
            'alert' => $alert,
        ]);
    }
}
