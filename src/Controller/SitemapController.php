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
        $lastmod = (new \DateTimeImmutable())->format('Y-m-d');

        $urls = [
            [
                'loc' => $urlGenerator->generate('main_home', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'priority' => '1.0',
                'lastmod' => $lastmod,
            ],
            [
                'loc' => $urlGenerator->generate('main_contact', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'priority' => '0.8',
                'lastmod' => $lastmod,
            ],
            [
                'loc' => $urlGenerator->generate('donation', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'priority' => '0.9',
                'lastmod' => $lastmod,
            ],
            [
                'loc' => $urlGenerator->generate('shop', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'priority' => '0.9',
                'lastmod' => $lastmod,
            ],
            [
                'loc' => $urlGenerator->generate('main_cgu', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'priority' => '0.4',
                'lastmod' => $lastmod,
            ],
            [
                'loc' => $urlGenerator->generate('main_pdc', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'priority' => '0.4',
                'lastmod' => $lastmod,
            ],
        ];

        $response = new Response(
            $this->renderView('sitemap/index.xml.twig', [
                'urls' => $urls,
            ])
        );

        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }
}
