<?php

namespace App\Service\Utils;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function upload(
        UploadedFile $file,
        string $directory,
        ?string $oldFileName = null
    ): string {
        $uploadDirectory = $this->projectDir . '/public/images/' . $directory;

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        if ($oldFileName) {
            $this->delete($oldFileName, $directory);
        }

        $newFileName = uniqid('', true) . '.' . $file->guessExtension();

        $file->move($uploadDirectory, $newFileName);

        return $newFileName;
    }

    public function delete(string $fileName, string $directory): void
    {
        $path = $this->projectDir . '/public/images/' . $directory . '/' . $fileName;

        if (file_exists($path)) {
            unlink($path);
        }
    }
}
