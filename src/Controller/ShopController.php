<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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

    #[Route('/cart/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function cart(
        int               $id,
        Request           $request,
        ArticleRepository $articleRepository,
        SessionInterface  $session
    ): Response
    {
        $article = $articleRepository->find($id);

        if (!$article) {
            throw $this->createNotFoundException('Article introuvable.');
        }

        $diameter = $request->request->get('diameter');

        if (!in_array($diameter, ['28', '32'], true)) {
            $this->addFlash('error', 'Merci de choisir un diamètre valide.');

            return $this->redirectToRoute('shop_article');
        }

        $cart = $session->get('cart', []);

        $cart[] = [
            'article_id' => $article->getId(),
            'diameter' => $diameter,
            'quantity' => 1,
        ];

        $session->set('cart', $cart);

        $this->addFlash('success', 'Article ajouté au panier.');

        return $this->redirectToRoute('shop');
    }
    #[Route('/cart', name: 'cart_index', methods: ['GET'])]
    public function cartIndex(
        SessionInterface $session,
        ArticleRepository $articleRepository
    ): Response {

        $cart = $session->get('cart', []);

        $cartWithData = [];

        foreach ($cart as $item) {

            $article = $articleRepository->find($item['article_id']);

            if ($article) {

                $cartWithData[] = [
                    'article' => $article,
                    'diameter' => $item['diameter'],
                    'quantity' => $item['quantity'],
                ];
            }
        }

        return $this->render('shop/cartIndex.html.twig', [
            'cart' => $cartWithData,
        ]);
    }

    }
