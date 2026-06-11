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

final class MainController extends AbstractController
{
    #[Route('/', name: 'main_home')]
    public function home(): Response
    {
        return $this->render('main/home.html.twig');
    }

    #[Route('/about', name: 'main_about')]
    public function about(): Response
    {
        return $this->render('main/about.html.twig');
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

    #[Route('/cgu', name: 'main_cgu')]
    public function cgu(): Response
    {
        return $this->render('main/cgu.html.twig');
    }

    #[Route('/pdc', name: 'main_pdc')]
    public function pdc(): Response
    {
        return $this->render('main/pdc.html.twig');
    }

    #[Route('/mentions-legales', name: 'main_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('main/mentionsLegales.html.twig');
    }

    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/presse', name: 'main_presse')]
    public function presse(): Response
    {
        return $this->render('main/presse.html.twig');
    }

    #[Route('/galerie', name: 'main_galerie')]
    public function galerie(): Response
    {
        return $this->render('main/galerie.html.twig', [
            'photos' => $this->getGalleryImages(),
        ]);
    }

    #[Route('/admin/gallery', name: 'admin_gallery')]
    public function galleryAdmin(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/adminGallery.html.twig', [
            'images' => $this->getGalleryImages(),
        ]);
    }

    #[Route('/admin/gallery/add', name: 'admin_gallery_add', methods: ['POST'])]
    public function addGalleryImage(
        Request       $request,
        ImageUploader $imageUploader
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $file = $request->files->get('gallery_image');

        if (!$file) {
            $this->addFlash('error', 'Aucune image sélectionnée.');

            return $this->redirectToRoute('admin_gallery');
        }

        $imageUploader->upload(
            $file,
            'gallery'
        );

        $this->addFlash('success', 'L’image a bien été ajoutée à la galerie.');

        return $this->redirectToRoute('admin_gallery');
    }

    #[Route('/admin/gallery/delete/{filename}', name: 'admin_gallery_delete', methods: ['POST'])]
    public function deleteGalleryImage(
        string        $filename,
        Request       $request,
        ImageUploader $imageUploader
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $filename = basename($filename);

        if (!$this->isCsrfTokenValid('delete_gallery_' . $filename, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $imageUploader->delete(
            $filename,
            'gallery'
        );

        $this->addFlash('success', 'L’image a bien été supprimée.');

        return $this->redirectToRoute('admin_gallery');
    }

    private function getGalleryImages(): array
    {
        $directory = $this->getParameter('kernel.project_dir') . '/public/images/gallery';

        $images = [];

        if (!is_dir($directory)) {
            return $images;
        }

        foreach (scandir($directory) as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $images[] = $file;
            }
        }

        sort($images);

        return $images;
    }


    #[Route('/carousel', name: 'main_carousel')]
    public function carousel(): Response
    {
        return $this->render('carousel/carousel.html.twig', [
            'images' => $this->getCarouselImages(),
        ]);
    }

    #[Route('/admin/carousel', name: 'admin_carousel')]
    public function carouselAdmin(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/adminCarousel.html.twig', [
            'images' => $this->getCarouselImages(),
        ]);
    }

    #[Route('/admin/carousel/add', name: 'admin_carousel_add', methods: ['POST'])]
    public function addCarouselImage(
        Request       $request,
        ImageUploader $imageUploader
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $file = $request->files->get('partenaire_image');

        if (!$file) {
            $this->addFlash('error', 'Aucune image sélectionnée.');

            return $this->redirectToRoute('admin_carousel');
        }

        $imageUploader->upload(
            $file,
            'partenaire'
        );

        $this->addFlash('success', 'L’image a bien été ajoutée au carousel.');

        return $this->redirectToRoute('admin_carousel');
    }

    #[Route('/admin/carousel/delete/{filename}', name: 'admin_carousel_delete', methods: ['POST'])]
    public function deleteCarouselImage(
        string        $filename,
        Request       $request,
        ImageUploader $imageUploader
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $filename = basename($filename);

        if (!$this->isCsrfTokenValid('delete_carousel_' . $filename, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $imageUploader->delete(
            $filename,
            'partenaire'
        );

        $this->addFlash('success', 'L’image a bien été supprimée du carousel.');

        return $this->redirectToRoute('admin_carousel');
    }

    private function getCarouselImages(): array
    {
        $directory = $this->getParameter('kernel.project_dir') . '/public/images/partenaire';

        $images = [];

        if (!is_dir($directory)) {
            return $images;
        }

        foreach (scandir($directory) as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $images[] = $file;
            }
        }

        sort($images);

        return $images;
    }

}
