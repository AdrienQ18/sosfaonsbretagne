<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function index(UrlGeneratorInterface $urlGenerator): Response
    {
        $lastmod = '2026-06-10';

        $urls = [
            $this->createUrl($urlGenerator, 'main_home', '1.0', $lastmod),
            $this->createUrl($urlGenerator, 'donation', '0.9', $lastmod),
            $this->createUrl($urlGenerator, 'main_alert', '0.9', $lastmod),
            $this->createUrl($urlGenerator, 'shop', '0.8', $lastmod),
            $this->createUrl($urlGenerator, 'main_about', '0.8', $lastmod),
            $this->createUrl($urlGenerator, 'main_contact', '0.7', $lastmod),
            $this->createUrl($urlGenerator, 'main_galerie', '0.6', $lastmod),
            $this->createUrl($urlGenerator, 'main_presse', '0.6', $lastmod),
            $this->createUrl($urlGenerator, 'event_list', '0.6', $lastmod),
            $this->createUrl($urlGenerator, 'main_cgu', '0.2', $lastmod),
            $this->createUrl($urlGenerator, 'main_mentions_legales', '0.2', $lastmod),
            $this->createUrl($urlGenerator, 'main_pdc', '0.2', $lastmod),
        ];

        $response = new Response(
            $this->renderView('sitemap/index.xml.twig', [
                'urls' => $urls,
            ])
        );

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }

    private function createUrl(
        UrlGeneratorInterface $urlGenerator,
        string $route,
        string $priority,
        string $lastmod
    ): array {
        return [
            'loc' => $urlGenerator->generate(
                $route,
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'priority' => $priority,
            'lastmod' => $lastmod,
        ];
    }
}
