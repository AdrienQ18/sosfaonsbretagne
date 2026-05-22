<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $eventsFiltered = $events;
//        $eventsFiltered = array_filter($events, fn($event) => $event->isPublished());

        return $this->render('main/list.html.twig', [
            'events' => $eventsFiltered,
        ]);
    }

    #[Route('/create', name: 'create')]
    public function create(
        Request                $request,
        EntityManagerInterface $entityManager,
        EventRepository        $eventRepository): Response
    {

        $event = new Event();
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
