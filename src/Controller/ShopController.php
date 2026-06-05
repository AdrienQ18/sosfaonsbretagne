<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\PreOrder;
use App\Entity\PreOrderItem;
use App\Entity\User;
use App\Enum\BirdhouseDiameter;
use App\Enum\PreOrderStatus;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Repository\PreOrderRepository;
use App\Service\ServiceHelloAsso\HelloAssoService;
use App\Service\ServiceHelloAsso\HelloAssoWebhookService;
use App\Service\Utils\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ServiceShop\PreOrderMailerService;
use App\Service\ServiceShop\PreOrderValidationService;

final class ShopController extends AbstractController
{
    #[Route('/boutique', name: 'shop')]
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

        $quantity = (int)$request->request->get('quantity', 1);

        $diameter = null;

        if ($article->isRequiresDiameter()) {
            $diameter = $request->request->get('diameter');

            if (!in_array($diameter, ['28', '32'], true)) {
                $this->addFlash('error', 'Merci de choisir un diamètre valide.');

                return $this->redirectToRoute('shop');
            }
        }

        if ($quantity < 1) {
            $this->addFlash('error', 'La quantité doit être supérieure à 0.');

            return $this->redirectToRoute('shop');
        }

        $cart = $session->get('cart', []);

        $cart[] = [
            'article_id' => $article->getId(),
            'diameter' => $diameter,
            'quantity' => $quantity,
        ];

        $session->set('cart', $cart);

        $this->addFlash('success', 'Article ajouté au panier.');

        return $this->redirectToRoute('shop');
    }

    #[Route('/cart', name: 'cart_index', methods: ['GET'])]
    public function cartIndex(
        SessionInterface  $session,
        ArticleRepository $articleRepository
    ): Response
    {
        $cart = $session->get('cart', []);
        $cartWithData = [];
        $totalCart = 0;

        foreach ($cart as $index => $item) {
            $article = $articleRepository->find($item['article_id']);

            if ($article) {
                $lineTotal = (float)$article->getPrice() * (int)$item['quantity'];
                $totalCart += $lineTotal;

                $cartWithData[] = [
                    'index' => $index,
                    'article' => $article,
                    'diameter' => $item['diameter'],
                    'quantity' => $item['quantity'],
                    'lineTotal' => $lineTotal,
                ];
            }
        }

        return $this->render('shop/cartIndex.html.twig', [
            'cart' => $cartWithData,
            'totalCart' => $totalCart,
        ]);
    }

    #[Route('/cart/remove/{index}', name: 'cart_remove', methods: ['POST'])]
    public function removeCartItem(
        int              $index,
        SessionInterface $session
    ): Response
    {
        $cart = $session->get('cart', []);

        if (isset($cart[$index])) {
            unset($cart[$index]);
            $cart = array_values($cart);

            $session->set('cart', $cart);

            $this->addFlash('success', 'Article supprimé du panier.');
        }

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/cart/update/{index}', name: 'cart_update', methods: ['POST'])]
    public function updateCartItem(
        int               $index,
        Request           $request,
        SessionInterface  $session,
        ArticleRepository $articleRepository,
    ): Response
    {
        $cart = $session->get('cart', []);

        if (!isset($cart[$index])) {
            $this->addFlash('error', 'Article introuvable dans le panier.');

            return $this->redirectToRoute('cart_index');
        }

        $article = $articleRepository->find(
            $cart[$index]['article_id']
        );

        if (!$article) {
            $this->addFlash('error', 'Article introuvable.');

            return $this->redirectToRoute('cart_index');
        }

        $quantity = (int)$request->request->get('quantity', 1);

        if ($quantity < 1) {
            $this->addFlash('error', 'La quantité doit être supérieure à 0.');

            return $this->redirectToRoute('cart_index');
        }

        $diameter = null;

        if ($article->isRequiresDiameter()) {

            $diameter = $request->request->get('diameter');

            if (!in_array($diameter, ['28', '32'], true)) {
                $this->addFlash('error', 'Diamètre invalide.');

                return $this->redirectToRoute('cart_index');
            }
        }

        $cart[$index]['diameter'] = $diameter;
        $cart[$index]['quantity'] = $quantity;

        $session->set('cart', $cart);

        $this->addFlash('success', 'Panier mis à jour.');

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/pre-order/validate', name: 'pre_order_validate', methods: ['POST'])]
    public function validatePreOrder(
        SessionInterface       $session,
        ArticleRepository      $articleRepository,
        EntityManagerInterface $entityManager,
        PreOrderMailerService  $mailerService,
    ): Response
    {
        $cart = $session->get('cart', []);

        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_index');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour valider une précommande.');
            return $this->redirectToRoute('app_login');
        }

        $preOrder = new PreOrder();
        $preOrder->setUser($user);
        $preOrder->setStatus(PreOrderStatus::EN_ATTENTE);
        $preOrder->setPreOrderDate(new \DateTimeImmutable());

        $totalAmount = 0;

        foreach ($cart as $item) {
            $article = $articleRepository->find($item['article_id']);

            if (!$article) {
                continue;
            }

            $quantity = (int)$item['quantity'];
            $unitPrice = (float)$article->getPrice();
            $totalPrice = $unitPrice * $quantity;

            $preOrderItem = new PreOrderItem();
            $preOrderItem->setArticle($article);
            $preOrderItem->setPreOrder($preOrder);
            $preOrderItem->setQuantity($quantity);
            $preOrderItem->setUnitPrice((string)$unitPrice);
            $preOrderItem->setTotalPrice((string)$totalPrice);
            if ($article->isRequiresDiameter()) {
                $preOrderItem->setDiameter(
                    BirdhouseDiameter::from((int)$item['diameter'])
                );
            } else {
                $preOrderItem->setDiameter(null);
            }
            $preOrder->addPreOrderItem($preOrderItem);
            $totalAmount += $totalPrice;
        }

        $preOrder->setTotalAmount((string)$totalAmount);

        $entityManager->persist($preOrder);
        $entityManager->flush();
        $mailerService->sendPreOrderConfirmation($preOrder);
        $mailerService->sendPreOrderNotificationToAssociation($preOrder);
        $session->remove('cart');

        $this->addFlash('success', 'Votre précommande a bien été envoyée.');
        return $this->redirectToRoute('shop');
    }

    #[Route('/admin/pre-order', name: 'admin_pre_order')]
    public function adminPreOrder(
        PreOrderRepository $preOrderRepository
    ): Response
    {

        $preOrders = $preOrderRepository->findBy(
            [],
            ['preOrderDate' => 'DESC']
        );

        return $this->render('shop/adminPreOrder.html.twig', [
            'preOrders' => $preOrders,
        ]);
    }

    #[Route('/admin/pre-order/{id}', name: 'admin_pre_order_show', methods: ['GET'])]
    public function showPreOrder(PreOrder $preOrder): Response
    {
        return $this->render('shop/adminPreOrderShow.html.twig', [
            'preOrder' => $preOrder,
        ]);
    }

    #[Route('/admin/pre-order/{id}/invoice', name: 'admin_pre_order_invoice', methods: ['GET'])]
    public function showPreOrderInvoice(PreOrder $preOrder): BinaryFileResponse
    {
        $pdfPath = $preOrder->getInvoicePdfPath();

        if (!$pdfPath || !file_exists($pdfPath)) {
            throw $this->createNotFoundException('La facture est introuvable.');
        }

        return $this->file(
            new File($pdfPath),
            'facture-precommande-' . $preOrder->getId() . '.pdf',
            ResponseHeaderBag::DISPOSITION_INLINE
        );
    }

    #[Route('/admin/pre-order/{id}/validate', name: 'admin_pre_order_validate', methods: ['POST'])]
    public function adminValidatePreOrder(
        PreOrder                  $preOrder,
        PreOrderValidationService $preOrderValidationService
    ): Response
    {
        $preOrderValidationService->validate($preOrder);

        $this->addFlash('success', 'Précommande validée, lien de paiement envoyé.');

        return $this->redirectToRoute('admin_pre_order');
    }

    #[Route(
        '/admin/pre-order/{id}/refuse',
        name: 'admin_pre_order_refuse',
        methods: ['POST']
    )]
    public function adminRefusePreOrder(
        PreOrder               $preOrder,
        EntityManagerInterface $entityManager
    ): Response
    {

        $preOrder->setStatus(
            PreOrderStatus::REFUSEE
        );

        $entityManager->flush();

        $this->addFlash(
            'success',
            'Précommande refusée.'
        );

        return $this->redirectToRoute('admin_pre_order');
    }

    #[Route('/pre-order/pay/{id}', name: 'pre_order_pay')]
    public function pay(
        PreOrder               $preOrder,
        HelloAssoService       $helloAssoService,
        EntityManagerInterface $entityManager
    ): Response
    {

        if ($preOrder->getStatus() === PreOrderStatus::PAYEE) {
            $this->addFlash('success', 'Précommande déjà payée.');

            return $this->redirectToRoute('shop');
        }

        $paymentUrl = $helloAssoService->createPreOrderCheckout($preOrder);

        $entityManager->flush();

        return $this->redirect($paymentUrl);
    }

    #[Route('/pre-order/payment/success/{id}', name: 'pre_order_payment_success', methods: ['GET'])]
    public function preOrderPaymentSuccess(PreOrder $preOrder): Response
    {
        return $this->render('shop/preOrderSuccess.html.twig', [
            'preOrder' => $preOrder,
        ]);
    }

    #[Route('/helloasso/webhook', name: 'helloasso_webhook', methods: ['POST'])]
    public function webhook(
        Request                 $request,
        HelloAssoWebhookService $webhookService
    ): JsonResponse
    {
        $receivedSecret = $request->query->get('secret');
        $expectedSecret = $_ENV['HELLOASSO_WEBHOOK_SECRET'];

        if (!$receivedSecret || !hash_equals($expectedSecret, $receivedSecret)) {
            return $this->json([
                'error' => 'Webhook non autorisé.',
            ], 403);
        }

        $payload = json_decode($request->getContent(), true);

        $result = $webhookService->handle($payload);

        return $this->json($result, $result['status']);
    }

    // Liste des articles
    #[Route('/admin/article', name: 'admin_article_index', methods: ['GET'])]
    public function showArticle(
        ArticleRepository $articleRepository,
        Request           $request,
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $articleList = $articleRepository->findAll();
        return $this->render('shop/adminArticleIndex.html.twig', [
            'articleList' => $articleList,
        ]);
    }

// Ajout article Ou Modification article
    #[Route('/admin/article/add', name: 'admin_article_add', methods: ['GET', 'POST'])]
    #[Route('/admin/article/update/{id}', name: 'admin_article_update', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function addOrUpdateArticle(
        Request                $request,
        ArticleRepository      $articleRepository,
        EntityManagerInterface $entityManager,
        FileUploader           $fileUploader,
        ?int                   $id = null,
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($id !== null) {
            $article = $articleRepository->find($id);

            if (!$article) {
                throw $this->createNotFoundException('L\'article n\'existe pas.');
            }
        } else {
            $article = new Article();
            $article->setUser($this->getUser());
        }
        $formAddArticle = $this->createForm(ArticleType::class, $article);
        $formAddArticle->handleRequest($request);

        if ($formAddArticle->isSubmitted() && $formAddArticle->isValid()) {
            $file = $formAddArticle->get('image')->getData();

            if ($file) {
                $article->setImage($fileUploader->upload($file, 'shop', $article->getImage()));
            }

            if ($id === null) {
                $entityManager->persist($article);
            }

            $entityManager->flush();

            $this->addFlash('success', $id === null ? 'Article ajouté avec succès.' : 'Article modifié avec succès.');

            return $this->redirectToRoute('admin_article_index');
        }

        return $this->render('shop/adminArticleAddOrUpdate.html.twig', [
            'article' => $article,
            'formAddArticle' => $formAddArticle->createView(),
        ]);
    }

// Suppression article
    #[Route('/admin/article/delete/{id}', name: 'admin_article_delete', methods: ['POST'])]
    public function deleteArticle(
        int                    $id,
        ArticleRepository      $articleRepository,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $article = $articleRepository->find($id);

        if (!$article) {
            throw $this->createNotFoundException('L\'article n\'existe pas.');
        }

        $entityManager->remove($article);
        $entityManager->flush();

        $this->addFlash('success', 'Votre article a bien été supprimé de la boutique.');

        return $this->redirectToRoute('admin_article_index');
    }


}
