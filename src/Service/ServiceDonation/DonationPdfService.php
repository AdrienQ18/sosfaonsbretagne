<?php

namespace App\Service\ServiceDonation;

use App\Entity\Donation;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

final class DonationPdfService
{
    public function __construct(
        private Environment $twig,
        private string $projectDir,
    ) {
    }

    public function generateFiscalReceipt(Donation $donation): string
    {
        $existingPath = $donation->getReceiptPdfPath();
        if ($existingPath && is_file($existingPath)) {
            return $existingPath;
        }

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);

        $logoRealPath = realpath($this->projectDir . '/public/images/logo.jpg');
        $signatureRealPath = realpath($this->projectDir . '/public/images/signature.jpg');

        if (!$logoRealPath || !$signatureRealPath) {
            throw new \RuntimeException('Logo ou signature introuvable.');
        }

        $logoPath = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoRealPath));
        $signaturePath = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($signatureRealPath));

        $receiptNumber = 'don-SOSFB-' . date('Y') . '-' . str_pad(
                (string) $donation->getId(),
                6,
                '0',
                STR_PAD_LEFT
            );

        $html = $this->twig->render('pdf/recu_Fiscale_Pdf_Donation.html.twig', [
            'donation' => $donation,
            'logoPath' => $logoPath,
            'signaturePath' => $signaturePath,
            'receiptNumber' => $receiptNumber,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $directory = $this->projectDir . '/var/receipts';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Impossible de créer le dossier %s', $directory));
        }

        $filename = 'Recu-fiscal-' . $receiptNumber . '.pdf';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        $bytes = file_put_contents($path, $dompdf->output());
        if ($bytes === false || !is_file($path)) {
            throw new \RuntimeException(sprintf('Écriture du PDF impossible : %s', $path));
        }

        $donation->setFiscalReceiptNumber($receiptNumber);
        $donation->setReceiptPdfPath($path);
        $donation->setReceiptGeneratedAt(new \DateTimeImmutable());

        return $path;
    }
}
