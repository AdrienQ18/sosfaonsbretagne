<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Service\Utils\ImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion des actualités.
 *
 * Il permet :
 * - d'afficher les actualités publiées côté public ;
 * - d'afficher toutes les actualités côté administrateur ;
 * - de créer, modifier ou supprimer une actualité.
 */
#[Route('/actualite', name: 'event_')]
final class EventController extends AbstractController
{
    /**
     * Affiche la liste des actualités.
     *
     * Les administrateurs voient toutes les actualités.
     * Les visiteurs voient uniquement les actualités publiées.
     */
    #[Route('', name: 'list')]
    public function list(
        EventRepository $eventRepository,
        Request $request,
        PaginatorInterface $paginator,
    ): Response {
        // Création de la requête de base, triée par date décroissante.
        $qb = $eventRepository->createQueryBuilder('e')
            ->orderBy('e.eventDate', 'DESC');

        // Si l'utilisateur n'est pas administrateur, seules les actualités publiées sont visibles.
        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb
                ->andWhere('e.isPublished = :published')
                ->setParameter('published', true);
        }

        // Pagination : 3 actualités affichées par page.
        $events = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            3
        );

        return $this->render('main/list.html.twig', [
            'events' => $events,
        ]);
    }

    /**
     * Crée une nouvelle actualité.
     *
     * L'actualité peut être enregistrée en brouillon
     * ou publiée directement selon le bouton utilisé.
     */
    #[Route('/ajouter', name: 'create')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ImageUploader $imageUploader,
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $event = new Event();

        // Création du formulaire d'actualité.
        $eventForm = $this->createForm(EventType::class, $event);
        $eventForm->handleRequest($request);

        if ($eventForm->isSubmitted() && $eventForm->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $eventForm->get('image')->getData();

            // Upload de l'image si un fichier a été envoyé.
            if ($file) {
                $event->setImage(
                    $imageUploader->upload($file, 'event/', $event->getImage())
                );
            }

            // Traitement commun : brouillon ou publication.
            $this->postProcess(
                $request,
                $entityManager,
                $event
            );

            return $this->redirectToRoute('event_list');
        }

        return $this->render('post/create.html.twig', [
            'eventForm' => $eventForm->createView(),
        ]);
    }

    /**
     * Modifie une actualité existante.
     *
     * L'actualité peut être remise en brouillon
     * ou publiée selon le bouton utilisé.
     */
    #[Route('/modifier/{id}', name: 'modify', methods: ['GET', 'POST'])]
    public function modify(
        Request $request,
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository,
        ImageUploader $imageUploader,
        int $id
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Recherche de l'actualité à modifier.
        $event = $eventRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Actualité non trouvée.');
        }

        $eventForm = $this->createForm(EventType::class, $event);
        $eventForm->handleRequest($request);

        if ($eventForm->isSubmitted() && $eventForm->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $eventForm->get('image')->getData();

            // Remplacement de l'image si une nouvelle image est envoyée.
            if ($file) {
                $event->setImage(
                    $imageUploader->upload($file, 'event/', $event->getImage())
                );
            }

            $this->postProcess(
                $request,
                $entityManager,
                $event
            );

            return $this->redirectToRoute('event_list');
        }

        return $this->render('post/create.html.twig', [
            'eventForm' => $eventForm->createView(),
        ]);
    }

    /**
     * Supprime une actualité et son image associée si elle existe.
     */
    #[Route('/supprimer/{id}', name: 'delete', methods: ['POST'])]
    public function delete(
        int $id,
        EventRepository $eventRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Recherche de l'actualité à supprimer.
        $event = $eventRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Actualité non trouvée.');
        }

        // On garde le nom de l'image avant de supprimer l'actualité en base.
        $imageFilename = $event->getImage();

        $entityManager->remove($event);
        $entityManager->flush();

        // Suppression physique de l'image associée à l'actualité.
        if ($imageFilename) {
            $imagePath = $this->getParameter('kernel.project_dir')
                . '/public/images/event/'
                . $imageFilename;

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $this->addFlash('success', 'Actualité supprimée avec succès.');

        return $this->redirectToRoute('event_list');
    }

    /**
     * Traitement commun après création ou modification d'une actualité.
     *
     * Selon le bouton cliqué :
     * - "enregistrer" garde l'actualité en brouillon ;
     * - "publier" rend l'actualité visible publiquement.
     */
    private function postProcess(
        Request $request,
        EntityManagerInterface $entityManager,
        Event $event
    ): void {
        $action = $request->request->get('action');
        $successMessage = 'Action non reconnue.';

        if ($action === 'enregistrer') {
            // Enregistrement en brouillon.
            $event->setIsPublished(false);
            $successMessage = 'Votre actualité a bien été enregistrée.';
        } elseif ($action === 'publier') {
            // Publication de l'actualité.
            $event->setIsPublished(true);
            $successMessage = 'Votre actualité a bien été publiée.';
        }

        $entityManager->persist($event);
        $entityManager->flush();

        $this->addFlash('success', $successMessage);
    }
}
