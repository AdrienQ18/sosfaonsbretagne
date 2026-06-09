<?php

namespace App\Service\Utils;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
class FileUploaderShop
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function uploadShop(
        UploadedFile $file,
        string $directory,
        ?string $oldFileName = null
    ): string {
        $uploadDirectory = $this->projectDir . '/public/images/' . $directory;

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        if ($oldFileName) {
            $this->deleteShop($oldFileName, $directory);
        }

        // Nom temporaire du fichier uploadé
        $tempFileName = uniqid('', true) . '.' . $file->guessExtension();

        $file->move(
            $uploadDirectory,
            $tempFileName
        );

        // Nom final du fichier optimisé
        $newFileName = uniqid('', true) . '.webp';

        $imagine = new Imagine();

        $image = $imagine->open(
            $uploadDirectory . '/' . $tempFileName
        );

        $image
            ->thumbnail(
                new Box(1200, 1200)
            )
            ->save(
                $uploadDirectory . '/' . $newFileName,
                [
                    'quality' => 85
                ]
            );

        // Suppression du fichier temporaire
        unlink(
            $uploadDirectory . '/' . $tempFileName
        );

        return $newFileName;
    }

    public function deleteShop(string $fileName, string $directory): void
    {
        $path = $this->projectDir . '/public/images/' . $directory . '/' . $fileName;

        if (file_exists($path)) {
            unlink($path);
        }
    }
}
