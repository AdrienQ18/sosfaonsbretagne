<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Service\Utils\ImageUploader;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur principal du site.
 *
 * Il gère :
 * - les pages publiques principales ;
 * - le formulaire de contact ;
 * - les pages légales ;
 * - le tableau de bord administrateur ;
 * - la galerie d'images ;
 * - le carousel des partenaires.
 */
final class MainController extends AbstractController
{
    /**
     * Affiche la page d'accueil.
     */
    #[Route('/', name: 'main_home')]
    public function home(): Response
    {
        return $this->render('main/home.html.twig');
    }

    /**
     * Affiche la page "À propos".
     */
    #[Route('/a-propos', name: 'main_about')]
    public function about(): Response
    {
        return $this->render('main/about.html.twig');
    }

    /**
     * Affiche et traite le formulaire de contact.
     *
     * Lorsqu'un message est envoyé, un email est transmis
     * à l'adresse de contact de l'association.
     */
    #[Route('/contact', name: 'main_contact', methods: ['GET', 'POST'])]
    public function contact(
        Request $request,
        MailerInterface $mailer
    ): Response {
        $formContact = $this->createForm(ContactType::class);
        $formContact->handleRequest($request);

        if ($formContact->isSubmitted() && $formContact->isValid()) {
            // Récupération des données validées du formulaire.
            $data = $formContact->getData();

            // Création de l'email envoyé à l'association.
            $email = (new TemplatedEmail())
                ->from(new Address(
                    'contact@sosfaonsbretagne.fr',
                    'SOS Faons Bretagne'
                ))
                ->replyTo(new Address(
                    $data['email'],
                    $data['firstname'] . ' ' . $data['lastname']
                ))
                ->to('contact@sosfaonsbretagne.fr')
                ->subject('[Contact site] ' . $data['subject'])
                ->htmlTemplate('emails/contact_notification.html.twig')
                ->context([
                    'contact' => $data,
                ]);

            $mailer->send($email);

            $this->addFlash(
                'success',
                'Votre message a bien été envoyé. Nous vous répondrons dès que possible.'
            );

            return $this->redirectToRoute('main_contact');
        }

        return $this->render('main/contact.html.twig', [
            'formContact' => $formContact->createView(),
        ]);
    }

    /**
     * Affiche les conditions générales d'utilisation.
     */
    #[Route('/conditions-generales-d-utilisation', name: 'main_cgu')]
    public function cgu(): Response
    {
        return $this->render('main/cgu.html.twig');
    }

    /**
     * Affiche la politique de confidentialité.
     */
    #[Route('/politique-de-confidentialite', name: 'main_pdc')]
    public function pdc(): Response
    {
        return $this->render('main/pdc.html.twig');
    }

    /**
     * Affiche les mentions légales.
     */
    #[Route('/mentions-legales', name: 'main_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('main/mentionsLegales.html.twig');
    }

    /**
     * Affiche le tableau de bord administrateur.
     */
    #[Route('/admin/tableau-de-bord', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dashboard.html.twig');
    }

    /**
     * Affiche la page presse.
     */
    #[Route('/presse', name: 'main_presse')]
    public function presse(): Response
    {
        return $this->render('main/presse.html.twig');
    }

    /**
     * Affiche la galerie publique.
     */
    #[Route('/galerie', name: 'main_galerie')]
    public function galerie(): Response
    {
        return $this->render('main/galerie.html.twig', [
            'photos' => $this->getGalleryImages(),
        ]);
    }

    /**
     * Affiche l'interface d'administration de la galerie.
     */
    #[Route('/admin/galerie', name: 'admin_gallery')]
    public function galleryAdmin(): Response
    {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/adminGallery.html.twig', [
            'images' => $this->getGalleryImages(),
        ]);
    }

    /**
     * Ajoute une image dans la galerie.
     */
    #[Route('/admin/galerie/ajouter', name: 'admin_gallery_add', methods: ['POST'])]
    public function addGalleryImage(
        Request $request,
        ImageUploader $imageUploader
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Récupération du fichier envoyé depuis le formulaire.
        $file = $request->files->get('gallery_image');

        if (!$file) {
            $this->addFlash('error', 'Aucune image sélectionnée.');

            return $this->redirectToRoute('admin_gallery');
        }

        // Upload dans le dossier public/images/gallery.
        // La validation du type et le nommage sécurisé sont portés par ImageUploader.
        $imageUploader->upload($file, 'gallery');

        $this->addFlash(
            'success',
            'L’image a bien été ajoutée à la galerie.'
        );

        return $this->redirectToRoute('admin_gallery');
    }

    /**
     * Supprime une image de la galerie.
     */
    #[Route('/admin/galerie/supprimer/{filename}', name: 'admin_gallery_delete', methods: ['GET', 'POST'])]
    public function deleteGalleryImage(
        string $filename,
        Request $request,
        ImageUploader $imageUploader
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Sécurise le nom du fichier pour éviter les chemins du type ../image.jpg.
        $filename = basename($filename);

        $csrfToken = $request->request->get('_token')
            ?? $request->query->get('_token');

        // Vérification CSRF avant suppression.
        if (!$this->isCsrfTokenValid(
            'delete_gallery_' . $filename,
            $csrfToken
        )) {
            throw $this->createAccessDeniedException();
        }

        // En prod, certains environnements peuvent renvoyer le formulaire en GET.
        // La suppression reste autorisée uniquement avec un token CSRF valide.
        $imageUploader->delete($filename, 'gallery');

        $this->addFlash('success', 'L’image a bien été supprimée.');

        return $this->redirectToRoute('admin_gallery');
    }

    /**
     * Récupère les images disponibles dans la galerie.
     *
     * Seules les extensions autorisées sont retournées.
     *
     * @return array<int, string>
     */
    private function getGalleryImages(): array
    {
        $directory = $this->getParameter('kernel.project_dir')
            . '/public/images/gallery';

        $images = [];

        // Si le dossier n'existe pas, on retourne un tableau vide.
        if (!is_dir($directory)) {
            return $images;
        }

        foreach (scandir($directory) as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            // Filtre pour ne récupérer que les fichiers image autorisés.
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $images[] = $file;
            }
        }

        sort($images);

        return $images;
    }

    /**
     * Affiche le carousel public des partenaires.
     */
    #[Route('/carousel', name: 'main_carousel')]
    public function carousel(): Response
    {
        $response = $this->render('carousel/carousel.html.twig', [
            'images' => $this->getCarouselImages(),
        ]);

        // Cette route sert de fragment réutilisable : elle ne doit pas être indexée comme une page autonome.
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }

    /**
     * Affiche l'interface d'administration du carousel.
     */
    #[Route('/admin/carousel', name: 'admin_carousel')]
    public function carouselAdmin(): Response
    {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/adminCarousel.html.twig', [
            'images' => $this->getCarouselImages(),
        ]);
    }

    /**
     * Ajoute une image au carousel des partenaires.
     */
    #[Route('/admin/carousel/ajouter', name: 'admin_carousel_add', methods: ['POST'])]
    public function addCarouselImage(
        Request $request,
        ImageUploader $imageUploader
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Récupération du fichier envoyé depuis le formulaire.
        $file = $request->files->get('partenaire_image');

        if (!$file) {
            $this->addFlash('error', 'Aucune image sélectionnée.');

            return $this->redirectToRoute('admin_carousel');
        }

        // Upload dans le dossier public/images/partenaire.
        // La validation du type et le nommage sécurisé sont portés par ImageUploader.
        $imageUploader->upload($file, 'partenaire');

        $this->addFlash(
            'success',
            'L’image a bien été ajoutée au carousel.'
        );

        return $this->redirectToRoute('admin_carousel');
    }

    /**
     * Supprime une image du carousel des partenaires.
     */
    #[Route('/admin/carousel/supprimer/{filename}', name: 'admin_carousel_delete', methods: ['GET', 'POST'])]
    public function deleteCarouselImage(
        string $filename,
        Request $request,
        ImageUploader $imageUploader
    ): Response {
        // Sécurité : accès réservé aux administrateurs.
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Sécurise le nom du fichier pour éviter les chemins du type ../image.jpg.
        $filename = basename($filename);

        $csrfToken = $request->request->get('_token')
            ?? $request->query->get('_token');

        // Vérification CSRF avant suppression.
        if (!$this->isCsrfTokenValid(
            'delete_carousel_' . $filename,
            $csrfToken
        )) {
            throw $this->createAccessDeniedException();
        }

        // En prod, certains environnements peuvent renvoyer le formulaire en GET.
        // La suppression reste autorisée uniquement avec un token CSRF valide.
        $imageUploader->delete($filename, 'partenaire');

        $this->addFlash(
            'success',
            'L’image a bien été supprimée du carousel.'
        );

        return $this->redirectToRoute('admin_carousel');
    }

    /**
     * Récupère les images disponibles pour le carousel des partenaires.
     *
     * Seule les extensions autorisées sont retournées.
     *
     * @return array<int, string>
     */
    private function getCarouselImages(): array
    {
        $directory = $this->getParameter('kernel.project_dir')
            . '/public/images/partenaire';

        $images = [];

        // Si le dossier n'existe pas, on retourne un tableau vide.
        if (!is_dir($directory)) {
            return $images;
        }

        foreach (scandir($directory) as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            // Filtre pour ne récupérer que les fichiers image autorisés.
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $images[] = $file;
            }
        }

        sort($images);

        return $images;
    }
}
