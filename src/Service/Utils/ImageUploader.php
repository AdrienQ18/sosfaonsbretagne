<?php

namespace App\Service\Utils;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Service de gestion des images.
 *
 * Ce service permet :
 * - d'uploader une image ;
 * - de convertir automatiquement les images en WebP ;
 * - de redimensionner les images ;
 * - de supprimer les anciennes images.
 */
class ImageUploader
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    /**
     * Upload une image et la convertit au format WebP.
     *
     * Les étapes sont les suivantes :
     * - création du dossier cible si nécessaire ;
     * - suppression éventuelle de l'ancienne image ;
     * - upload du fichier temporaire ;
     * - redimensionnement ;
     * - conversion en WebP ;
     * - suppression du fichier temporaire.
     *
     * @param UploadedFile $file Image à traiter
     * @param string $directory Dossier cible dans public/images
     * @param string|null $oldFileName Ancienne image à supprimer
     * @param int $maxWidth Largeur maximale
     * @param int $maxHeight Hauteur maximale
     * @param int $quality Qualité de compression WebP
     *
     * @return string Nom du nouveau fichier généré
     */
    public function upload(
        UploadedFile $file,
        string $directory,
        ?string $oldFileName = null,
        int $maxWidth = 1920,
        int $maxHeight = 1920,
        int $quality = 85
    ): string {
        $uploadDirectory = $this->projectDir
            . '/public/images/'
            . trim($directory, '/');

        // Création automatique du dossier s'il n'existe pas.
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        // Suppression de l'ancienne image lors d'une mise à jour.
        if ($oldFileName) {
            $this->delete($oldFileName, $directory);
        }

        /**
         * Nom temporaire utilisé pour enregistrer
         * le fichier uploadé avant conversion.
         */
        $tempFileName = uniqid('', true)
            . '.'
            . $file->guessExtension();

        $file->move(
            $uploadDirectory,
            $tempFileName
        );

        /**
         * Nom final du fichier WebP.
         *
         * L'utilisation d'un identifiant unique évite
         * les collisions entre deux images.
         */
        $newFileName = uniqid('', true) . '.webp';

        $imagine = new Imagine();

        // Ouverture de l'image uploadée.
        $image = $imagine->open(
            $uploadDirectory . '/' . $tempFileName
        );

        /**
         * Redimensionnement automatique.
         *
         * Le ratio est conservé afin d'éviter toute déformation.
         */
        $image
            ->thumbnail(
                new Box($maxWidth, $maxHeight)
            )
            ->save(
                $uploadDirectory . '/' . $newFileName,
                [
                    'quality' => $quality,
                ]
            );

        // Suppression du fichier temporaire après conversion.
        unlink($uploadDirectory . '/' . $tempFileName);

        return $newFileName;
    }

    /**
     * Supprime une image du serveur.
     *
     * @param string $fileName Nom du fichier
     * @param string $directory Dossier contenant le fichier
     */
    public function delete(
        string $fileName,
        string $directory
    ): void {
        /**
         * Sécurisation du nom du fichier.
         *
         * basename() empêche les tentatives de traversal
         * de répertoires (../../etc/passwd).
         */
        $fileName = basename($fileName);

        $path = $this->projectDir
            . '/public/images/'
            . trim($directory, '/')
            . '/'
            . $fileName;

        // Suppression uniquement si le fichier existe.
        if (is_file($path)) {
            unlink($path);
        }
    }
}
