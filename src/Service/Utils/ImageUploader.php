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
    private const BITMAP_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const SVG_EXTENSION = 'svg';

    public function __construct(
        private string $projectDir,
    ) {
    }

    /**
     * Upload une image.
     *
     * Les étapes sont les suivantes :
     * - création du dossier cible si nécessaire ;
     * - suppression éventuelle de l'ancienne image ;
     * - conservation des SVG validés ;
     * - conversion des images bitmap en WebP.
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

        // Le répertoire cible est volontairement limité à public/images.
        // Les contrôleurs ne transmettent que le sous-dossier métier.
        // Création automatique du dossier s'il n'existe pas.
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        // Suppression de l'ancienne image lors d'une mise à jour.
        if ($oldFileName) {
            // On supprime avant conversion pour éviter d'avoir deux images
            // actives pour la même entité en cas de remplacement.
            $this->delete($oldFileName, $directory);
        }

        $extension = strtolower(
            $file->guessExtension()
            ?: $file->getClientOriginalExtension()
        );

        if ($extension === self::SVG_EXTENSION) {
            return $this->uploadSvg($file, $uploadDirectory);
        }

        if (!in_array($extension, self::BITMAP_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Format image non autorisé.');
        }

        /**
         * Nom temporaire utilisé pour enregistrer
         * le fichier uploadé avant conversion.
         */
        $tempFileName = uniqid('', true)
            . '.'
            . $extension;

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
        // Si le fichier n'est pas une image lisible, Imagine lèvera une exception.
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
     * Enregistre un SVG sans conversion WebP.
     *
     * Les SVG sont volontairement contrôlés avant déplacement afin d'éviter
     * les scripts ou gestionnaires d'événements injectés dans le fichier.
     */
    private function uploadSvg(
        UploadedFile $file,
        string $uploadDirectory
    ): string {
        $content = file_get_contents($file->getPathname());

        if (
            $content === false
            || !preg_match('/<svg[\s>]/i', $content)
            || preg_match('/<script\b|on[a-z]+\s*=|javascript:/i', $content)
        ) {
            throw new \InvalidArgumentException('SVG non valide ou potentiellement dangereux.');
        }

        $newFileName = uniqid('', true) . '.svg';

        $file->move(
            $uploadDirectory,
            $newFileName
        );

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
