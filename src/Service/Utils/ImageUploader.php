<?php

namespace App\Service\Utils;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploader
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function upload(
        UploadedFile $file,
        string $directory,
        ?string $oldFileName = null,
        int $maxWidth = 1920,
        int $maxHeight = 1920,
        int $quality = 85
    ): string {
        $uploadDirectory = $this->projectDir . '/public/images/' . trim($directory, '/');

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        if ($oldFileName) {
            $this->delete($oldFileName, $directory);
        }

        $tempFileName = uniqid('', true) . '.' . $file->guessExtension();

        $file->move($uploadDirectory, $tempFileName);

        $newFileName = uniqid('', true) . '.webp';

        $imagine = new Imagine();

        $image = $imagine->open($uploadDirectory . '/' . $tempFileName);

        $image
            ->thumbnail(new Box($maxWidth, $maxHeight))
            ->save($uploadDirectory . '/' . $newFileName, [
                'quality' => $quality,
            ]);

        unlink($uploadDirectory . '/' . $tempFileName);

        return $newFileName;
    }

    public function delete(string $fileName, string $directory): void
    {
        $fileName = basename($fileName);

        $path = $this->projectDir
            . '/public/images/'
            . trim($directory, '/')
            . '/'
            . $fileName;

        if (is_file($path)) {
            unlink($path);
        }
    }
}
