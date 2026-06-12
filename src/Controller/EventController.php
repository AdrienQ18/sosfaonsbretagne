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

#[Route('/actualite', name: 'event_')]
final class EventController extends AbstractController
{
    #[Route('', name: 'list')]
    public function list(
        EventRepository    $eventRepository,
        Request            $request,
        PaginatorInterface $paginator,
    ): Response
    {
        $qb = $eventRepository->createQueryBuilder('e')
            ->orderBy('e.eventDate', 'DESC');

        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb
                ->andWhere('e.isPublished = :published')
                ->setParameter('published', true);
        }

        $events = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            3
        );

        return $this->render('main/list.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/ajouter', name: 'create')]
    public function create(
        Request                $request,
        EntityManagerInterface $entityManager,
      ImageUploader         $imageUploader,
    ): Response
    {

        $event = new Event();
        $eventForm = $this->createForm(EventType::class, $event);

        $eventForm->handleRequest($request);
        if ($eventForm->isSubmitted() && $eventForm->isValid()) {
            /**
             * @var UploadedFile $file
             */
            $file = $eventForm->get('image')->getData();
            if ($file) {

                $event->setImage(
                    $imageUploader->upload($file, 'event/', $event->getImage()),
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

    #[Route('/modifier/{id}', name: 'modify', methods: ['GET', 'POST'])]
    public function modify(
        Request                $request,
        EntityManagerInterface $entityManager,
        EventRepository        $eventRepository,
        int                    $id): Response
    {

        $event = $eventRepository->find($id);
        $eventForm = $this->createForm(EventType::class, $event);
        $eventForm->handleRequest($request);
        if ($eventForm->isSubmitted() && $eventForm->isValid()) {
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

    #[Route('/supprimer/{id}', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(
        int                    $id,
        EventRepository        $eventRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $event = $eventRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Actualité non trouvée.');
        }

        //Suppression de l'activité
        $imageFilename = $event->getImage();

        $entityManager->remove($event);
        $entityManager->flush();

        //Suppression de l'image
        if ($imageFilename) {
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/images/event/' . $imageFilename;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $this->addFlash('success', 'Activité supprimée avec succès');
        return $this->redirectToRoute('event_list');
    }

    public function postProcess(
        Request                $request,
        EntityManagerInterface $entityManager,
        Event                  $event
    ): void
    {
        $action = $request->request->get('action');
        $successMessage = 'undefined';
        if ($action === 'enregistrer') {
            //Enregistrement du post
            $event->setIsPublished(false);
            $successMessage = 'Votre post a bien été enregistré';
        } else if ($action === 'publier') {
            //Publication du post
            $event->setIsPublished(true);
            $successMessage = 'Votre post à bien été publié';
        }
        $entityManager->persist($event);
        $entityManager->flush();
        $this->addFlash('success', $successMessage);
    }
}
