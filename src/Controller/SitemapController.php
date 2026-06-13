<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Contrôleur de génération du sitemap XML.
 *
 * Le sitemap permet aux moteurs de recherche
 * (Google, Bing, etc.) de découvrir et indexer
 * les principales pages du site.
 */
final class SitemapController extends AbstractController
{
    /**
     * Génère le fichier sitemap.xml.
     *
     * Chaque URL contient :
     * - son adresse absolue ;
     * - sa priorité SEO ;
     * - sa date de dernière modification.
     */
    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function index(
        UrlGeneratorInterface $urlGenerator
    ): Response {
        /**
         * Date de dernière modification utilisée dans le sitemap.
         *
         * À terme, cette valeur pourrait être récupérée
         * dynamiquement depuis la base de données ou
         * la date de mise à jour des contenus.
         */
        $lastmod = '2026-06-10';

        // Liste des URLs publiques à inclure dans le sitemap.
        // Les routes privées, admin et paiement ne sont pas exposées aux moteurs.
        $urls = [
            $this->createUrl($urlGenerator, 'main_home', '1.0', 'weekly', $lastmod),
            $this->createUrl($urlGenerator, 'donation', '0.9', 'monthly', $lastmod),
            $this->createUrl($urlGenerator, 'main_alert', '0.9', 'monthly', $lastmod),
            $this->createUrl($urlGenerator, 'shop', '0.8', 'weekly', $lastmod),
            $this->createUrl($urlGenerator, 'main_about', '0.8', 'monthly', $lastmod),
            $this->createUrl($urlGenerator, 'main_contact', '0.7', 'monthly', $lastmod),
            $this->createUrl($urlGenerator, 'main_galerie', '0.6', 'monthly', $lastmod),
            $this->createUrl($urlGenerator, 'main_presse', '0.6', 'monthly', $lastmod),
            $this->createUrl($urlGenerator, 'event_list', '0.6', 'weekly', $lastmod),
            $this->createUrl($urlGenerator, 'main_cgu', '0.2', 'yearly', $lastmod),
            $this->createUrl($urlGenerator, 'main_mentions_legales', '0.2', 'yearly', $lastmod),
            $this->createUrl($urlGenerator, 'main_pdc', '0.2', 'yearly', $lastmod),
        ];

        // Génération du contenu XML à partir du template Twig.
        $response = new Response(
            $this->renderView('sitemap/index.xml.twig', [
                'urls' => $urls,
            ])
        );

        // Définition du type MIME XML pour les moteurs de recherche.
        $response->headers->set(
            'Content-Type',
            'application/xml; charset=UTF-8'
        );

        return $response;
    }

    /**
     * Génère les informations d'une URL du sitemap.
     *
     * @param UrlGeneratorInterface $urlGenerator Service de génération d'URL
     * @param string $route Nom de la route Symfony
     * @param string $priority Priorité SEO (0.0 à 1.0)
     * @param string $changefreq Fréquence indicative de mise à jour
     * @param string $lastmod Date de dernière modification
     *
     * @return array{
     *     loc: string,
     *     priority: string,
     *     changefreq: string,
     *     lastmod: string
     * }
     */
    private function createUrl(
        UrlGeneratorInterface $urlGenerator,
        string $route,
        string $priority,
        string $changefreq,
        string $lastmod
    ): array {
        return [
            // Génération de l'URL absolue requise par le protocole sitemap.
            'loc' => $urlGenerator->generate(
                $route,
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),

            // Priorité indicative pour les moteurs de recherche.
            'priority' => $priority,

            // Fréquence indicative de mise à jour de la page.
            'changefreq' => $changefreq,

            // Date de dernière modification de la page.
            'lastmod' => $lastmod,
        ];
    }
}
