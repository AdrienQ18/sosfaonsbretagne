<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Service\Utils\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/event', name: 'event_')]
final class EventController extends AbstractController
{
    #[Route('', name: 'list')]
    public function list(
        EventRepository $eventRepository,
        Request         $request,
    ): Response
    {

        $events = $eventRepository->findAll();

        if ($this->isGranted('ROLE_ADMIN')) {    // L'utilisateur a le rôle ROLE_ADMIN}
            $eventsFiltered = $events;
        } else {
            $eventsFiltered = array_filter($events, fn($event) => $event->isPublished());
        }

        // Tri par ordre inverse chronologique (du plus récent au plus ancien)
        usort($eventsFiltered, function($a, $b) {
            return $b->getEventDate() <=> $a->getEventDate();
        });

        return $this->render('main/list.html.twig', [
            'events' => $eventsFiltered,
        ]);
    }

    #[Route('/create', name: 'create')]
    public function create(
        Request                $request,
        EntityManagerInterface $entityManager,
        EventRepository        $eventRepository,
        FileUploader $fileUploader): Response
    {

        $event = new Event();
        $eventForm = $this->createForm(EventType::class, $event);

        $eventForm->handleRequest($request);
        if ($eventForm->isSubmitted() && $eventForm->isValid()){
            /**
             * @var UploadedFile $file
             */
            dump("Try to upload file :" );
            $file = $eventForm->get('image')->getData();
            if ($file) {

                $event->setImage(
                    $fileUploader->upload($file, 'event/', $event->getImage()),
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

    #[Route('/modify/{id}', name: 'modify', methods: ['GET', 'POST'])]
    public function modify(
        Request                $request,
        EntityManagerInterface $entityManager,
        EventRepository        $eventRepository,
        int                    $id): Response
    {

        $event = $eventRepository->find($id);
        $eventForm = $this->createForm(EventType::class, $event);
        $eventForm->handleRequest($request);
        if ($eventForm->isSubmitted() && $eventForm->isValid()){
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
