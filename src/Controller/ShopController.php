<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\PreOrder;
use App\Entity\PreOrderItem;
use App\Entity\User;
use App\Enum\BirdhouseDiameter;
use App\Enum\PreOrderStatus;
use App\Form\ArticleType;
use App\Form\PreOrderFilterType;
use App\Repository\ArticleRepository;
use App\Repository\PreOrderRepository;
use App\Service\HelloAsso\HelloAssoService;
use App\Service\Utils\ImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Shop\PreOrderMailerService;
use App\Service\Shop\PreOrderManagementService;

/**
 * Contrôleur de gestion de la boutique.
 *
 * Il permet :
 * - la consultation des articles ;
 * - la gestion du panier en session ;
 * - la création des précommandes ;
 * - le paiement des précommandes via HelloAsso ;
 * - la gestion administrative des précommandes ;
 * - la gestion administrative des articles.
 */
final class ShopController extends AbstractController
{
    /**
     * Affiche la liste des articles disponibles dans la boutique.
     */
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

    /**
     * Ajoute un article au panier.
     *
     * Le panier est stocké dans la session utilisateur.
     * Si le même article avec le même diamètre existe déjà,
     * la quantité est incrémentée.
     */
    #[Route('/panier/ajouter/{id}', name: 'cart_add', methods: ['POST'])]
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
        // Vérification du token CSRF avant modification du panier.
        if (!$this->isCsrfTokenValid('cart_add_' . $article->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $quantity = (int)$request->request->get('quantity', 1);

        $diameter = null;

        // Contrôle spécifique aux nichoirs nécessitant un diamètre.
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

        $itemFound = false;

        // Le panier ne stocke que les données nécessaires en session.
        // Les entités Article sont rechargées depuis la base à l'affichage.
        // Fusion des lignes identiques dans le panier.
        foreach ($cart as &$item) {
            if (
                $item['article_id'] === $article->getId()
                && $item['diameter'] === $diameter
            ) {
                $item['quantity'] += $quantity;
                $itemFound = true;
                break;
            }
        }

        unset($item);

        if (!$itemFound) {
            $cart[] = [
                'article_id' => $article->getId(),
                'diameter' => $diameter,
                'quantity' => $quantity,
            ];
        }

        $session->set('cart', $cart);

        $this->addFlash('success', 'Article ajouté au panier.');

        return $this->redirectToRoute('shop');
    }

    /**
     * Affiche le contenu du panier.
     *
     * Calcule également le montant total de la commande.
     */
    #[Route('/panier', name: 'cart_index', methods: ['GET'])]
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
    /**
     * Supprime un article du panier.
     */
    #[Route('/panier/supprimer/{index}', name: 'cart_remove', methods: ['POST'])]
    public function removeCartItem(
        int              $index,
        Request          $request,
        SessionInterface $session
    ): Response
    {
        if (!$this->isCsrfTokenValid(
            'cart_remove_' . $index,
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $cart = $session->get('cart', []);

        if (isset($cart[$index])) {
            unset($cart[$index]);

            // Réindexation indispensable : les index servent ensuite d'identifiant
            // de ligne dans les routes de modification et de suppression.
            $cart = array_values($cart);

            $session->set('cart', $cart);

            $this->addFlash('success', 'Article supprimé du panier.');
        }

        return $this->redirectToRoute('cart_index');
    }
    /**
     * Met à jour la quantité ou le diamètre d'un article du panier.
     */
    #[Route('/panier/modifier/{index}', name: 'cart_update', methods: ['POST'])]
    public function updateCartItem(
        int               $index,
        Request           $request,
        SessionInterface  $session,
        ArticleRepository $articleRepository,
    ): Response
    {
        if (!$this->isCsrfTokenValid(
            'cart_update_' . $index,
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

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
    /**
     * Transforme le panier en précommande.
     *
     * La précommande est enregistrée en base,
     * les lignes de commande sont créées
     * puis les emails de confirmation sont envoyés.
     */
    #[Route('/precommande/valider', name: 'pre_order_validate', methods: ['POST'])]
    public function validatePreOrder(
        SessionInterface       $session,
        ArticleRepository      $articleRepository,
        EntityManagerInterface $entityManager,
        PreOrderMailerService  $mailerService,
        Request                $request
    ): Response
    {
        if (
            !$this->isCsrfTokenValid(
                'pre_order_validate',
                $request->request->get('_token')
            )
        ) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }
        $cart = $session->get('cart', []);

        if (empty($cart)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_index');
        }
// Une précommande nécessite obligatoirement un utilisateur connecté.
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

        // On fige les prix dans les lignes de précommande pour conserver
        // l'historique même si le prix d'un article change ensuite.
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
                if (empty($item['diameter'])) {
                    $this->addFlash('error', 'Un diamètre est manquant dans votre panier.');

                    return $this->redirectToRoute('cart_index');
                }

                try {
                    $preOrderItem->setDiameter(
                        BirdhouseDiameter::from((int) $item['diameter'])
                    );
                } catch (\ValueError) {
                    $this->addFlash('error', 'Un diamètre invalide est présent dans votre panier.');

                    return $this->redirectToRoute('cart_index');
                }
            } else {
                $preOrderItem->setDiameter(null);
            }
            $preOrder->addPreOrderItem($preOrderItem);
            $totalAmount += $totalPrice;
        }
        if ($preOrder->getPreOrderItems()->isEmpty()) {
            $this->addFlash('error', 'Aucun article valide dans le panier.');

            return $this->redirectToRoute('cart_index');
        }

        $preOrder->setTotalAmount((string)$totalAmount);

        $entityManager->persist($preOrder);
        $entityManager->flush();

        // Les emails sont envoyés après le flush afin d'avoir un numéro
        // de précommande fiable dans les templates et les liens.
        try {
            $mailerService->sendPreOrderConfirmation($preOrder);
            $mailerService->sendPreOrderNotificationToAssociation($preOrder);
        } catch (\Throwable $e) {
            $this->addFlash(
                'warning',
                'Votre précommande a été enregistrée, mais l’email de confirmation n’a pas pu être envoyé.'
            );
        }
        $session->remove('cart');

        $this->addFlash('success', 'Votre précommande a bien été envoyée.');
        return $this->redirectToRoute('shop');
    }

    #[Route('/admin/precommande', name: 'admin_pre_order')]
    public function adminPreOrder(
        Request            $request,
        PreOrderRepository $preOrderRepository,
        PaginatorInterface $paginator
    ): Response
    {
        // La liste admin reprend le même modèle que les autres écrans :
        // filtres en GET pour conserver les critères dans l'URL.
        $form = $this->createForm(PreOrderFilterType::class, null, [
            'method' => 'GET',
        ]);

        $form->handleRequest($request);

        $filters = $form->getData() ?? [];

        $query = $preOrderRepository->findByFiltersQuery($filters);

        $preOrders = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('shop/adminPreOrder.html.twig', [
            'preOrders' => $preOrders,
            'filterForm' => $form->createView(),
        ]);
    }
    /**
     * Affiche le détail d'une précommande.
     */
    #[Route('/admin/precommande/{id}', name: 'admin_pre_order_show', methods: ['GET'])]
    public function showPreOrder(PreOrder $preOrder): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('shop/adminPreOrderShow.html.twig', [
            'preOrder' => $preOrder,
        ]);
    }

    #[Route('/admin/precommande/{id}/facture', name: 'admin_pre_order_invoice', methods: ['GET'])]
    public function showPreOrderInvoice(PreOrder $preOrder): BinaryFileResponse
    {
        $pdfPath = $preOrder->getInvoicePdfPath();

        // La facture est générée hors de ce contrôleur : on vérifie donc
        // explicitement que le chemin existe avant de l'exposer.
        if (!$pdfPath || !file_exists($pdfPath)) {
            throw $this->createNotFoundException('La facture est introuvable.');
        }

        return $this->file(
            new File($pdfPath),
            'facture-precommande-' . $preOrder->getId() . '.pdf',
            ResponseHeaderBag::DISPOSITION_INLINE
        );
    }

    #[Route('/admin/precommande/{id}/valider', name: 'admin_pre_order_validate', methods: ['POST'])]
    public function adminValidatePreOrder(
        PreOrder                  $preOrder,
        PreOrderManagementService $preOrderValidationService
    ): Response
    {
        // Toute la logique métier de validation est centralisée dans le service :
        // changement de statut, génération du lien de paiement et notification.
        $preOrderValidationService->validate($preOrder);

        $this->addFlash('success', 'Précommande validée, lien de paiement envoyé.');

        return $this->redirectToRoute('admin_pre_order');
    }

    #[Route(
        '/admin/precommande/{id}/refuser',
        name: 'admin_pre_order_refuse',
        methods: ['POST']
    )]
    public function adminRefusePreOrder(
        PreOrder $preOrder,
        PreOrderManagementService $preOrderValidationService
    ): Response {
        $preOrderValidationService->refuse($preOrder);

        $this->addFlash('success', 'Précommande refusée. Un email a été envoyé à l’utilisateur.');

        return $this->redirectToRoute('admin_pre_order');
    }

    #[Route('/precommande/payer/{id}', name: 'pre_order_pay')]
    public function pay(
        PreOrder               $preOrder,
        HelloAssoService       $helloAssoService,
        EntityManagerInterface $entityManager
    ): Response
    {

        // Une précommande déjà payée ne doit jamais recréer un checkout.
        if ($preOrder->getStatus() === PreOrderStatus::PAYEE) {
            $this->addFlash('success', 'Précommande déjà payée.');

            return $this->redirectToRoute('shop');
        }

        $paymentUrl = $helloAssoService->createPreOrderCheckout($preOrder);

        // Le service peut enrichir la précommande avec des informations HelloAsso.
        $entityManager->flush();

        return $this->redirect($paymentUrl['redirectUrl']);
    }

    #[Route('/pre-order/paiement/valider/{id}', name: 'pre_order_payment_success', methods: ['GET'])]
    public function preOrderPaymentSuccess(PreOrder $preOrder): Response
    {

        $this->addFlash(
            'success',
            'Paiement en cours de validation. Votre facture sera envoyée par email.'
        );

        return $this->redirectToRoute('main_home');
    }


    // Liste des articles
    #[Route('/admin/article', name: 'admin_article_index', methods: ['GET'])]
    public function showArticle(
        ArticleRepository $articleRepository,
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $articleList = $articleRepository->findAll();
        return $this->render('shop/adminArticleIndex.html.twig', [
            'articleList' => $articleList,
        ]);
    }

// Ajout article Ou Modification article
    #[Route('/admin/article/ajouter', name: 'admin_article_add', methods: ['GET', 'POST'])]
    #[Route('/admin/article/modifier/{id}', name: 'admin_article_update', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function addOrUpdateArticle(
        Request                $request,
        ArticleRepository      $articleRepository,
        EntityManagerInterface $entityManager,
        ImageUploader          $imageUploader,
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

            // En édition, l'ancien nom d'image est transmis à l'uploader pour
            // permettre son remplacement propre si une nouvelle image est fournie.
            if ($file) {
                $article->setImage($imageUploader->upload($file, 'shop', $article->getImage()));
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
    #[Route('/admin/article/supprimer/{id}', name: 'admin_article_delete', methods: ['POST'])]
    public function deleteArticle(
        int                    $id,
        ArticleRepository      $articleRepository,
        EntityManagerInterface $entityManager,
        Request                $request
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $article = $articleRepository->find($id);

        if (!$article) {
            throw $this->createNotFoundException('L\'article n\'existe pas.');
        }

        if (!$this->isCsrfTokenValid(
            'delete_article_' . $article->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $entityManager->remove($article);
        $entityManager->flush();

        $this->addFlash('success', 'Votre article a bien été supprimé de la boutique.');

        return $this->redirectToRoute('admin_article_index');
    }


}
