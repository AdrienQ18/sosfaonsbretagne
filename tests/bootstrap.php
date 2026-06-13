<?php

use Symfony\Component\Dotenv\Dotenv;

// Chargement de l'autoloader Composer.
require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Chargement des variables d'environnement.
 *
 * Symfony lit automatiquement les fichiers :
 * - .env
 * - .env.local
 * - .env.dev
 * - .env.prod
 * selon l'environnement courant.
 */
if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

/**
 * En environnement de développement,
 * les fichiers créés par PHP sont accessibles
 * à tous les utilisateurs du système.
 */
if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
