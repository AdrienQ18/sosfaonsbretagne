<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/contact', name: 'main_contact')]
    public function contact(): Response{
        return $this->render('main/contact.html.twig');
    }

    #[Route('/galerie', name: 'main_galerie')]
    public function galerie(): Response{
        return $this->render('main/galerie.html.twig');
    }
}
