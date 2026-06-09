<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route('/', name: 'main_home')]
    public function home(): Response
    {
        return $this->render('main/home.html.twig');
    }

    #[Route('/about', name: 'main_about')]
    public function about(): Response{
        return $this->render('main/about.html.twig');
    }

    #[Route ('/actions', name: 'main_actions')]
    public function actions(): Response{
        return $this->render('main/actions.html.twig');
    }

    #[Route('/contact', name: 'main_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $formContact = $this->createForm(ContactType::class);
        $formContact->handleRequest($request);

        if ($formContact->isSubmitted() && $formContact->isValid()) {
            $data = $formContact->getData();

            $email = (new TemplatedEmail())
                ->from(new Address('contact@sosfaonsbretagne.fr', 'SOS Faons Bretagne'))
                ->replyTo(new Address($data['email'], $data['firstname'] . ' ' . $data['lastname']))
                ->to('contact@sosfaonsbretagne.fr')
                ->subject('[Contact site] ' . $data['subject'])
                ->htmlTemplate('emails/contact_notification.html.twig')
                ->context([
                    'contact' => $data,
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Votre message a bien été envoyé. Nous vous répondrons dès que possible.');

            return $this->redirectToRoute('main_contact');
        }

        return $this->render('main/contact.html.twig', [
            'formContact' => $formContact->createView(),
        ]);
    }

    #[Route('/galerie', name: 'main_galerie')]
    public function galerie(): Response{
        return $this->render('main/galerie.html.twig');
    }

    #[Route('/cgu', name: 'main_cgu')]
    public function cgu(): Response{
        return $this->render('main/cgu.html.twig');
    }

    #[Route('/pdc', name: 'main_pdc')]
    public function pdc(): Response{
        return $this->render('main/pdc.html.twig');
    }
#[Route('/mentions-legales', name: 'main_mentions_legales')]
public function mentionsLegales(): Response{
    return $this->render('main/mentionsLegales.html.twig');
}
}
