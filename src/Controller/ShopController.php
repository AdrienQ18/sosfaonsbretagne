<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShopController extends AbstractController
{
    #[Route('/shop', name: 'shop')]
    public function index(
        ArticleRepository $articleRepository,
    ): Response
    {
        $articleList = $articleRepository->findAll();
        return $this->render('shop/article.html.twig', [
            'articleList' => $articleList,
        ]);
    }
}
