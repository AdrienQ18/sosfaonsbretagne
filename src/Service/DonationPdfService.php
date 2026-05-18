<?php

namespace App\Service;

use App\Entity\Donation;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class DonationPdfService
{
    public function __construct(
        private Environment $twig,
        private string $projectDir,
    ) {
    }

    public function generateFiscalReceipt(Donation $donation): string
    {
        if ($donation->getReceiptPdfPath()) {
            return $donation->getReceiptPdfPath();
        }

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
//        $options->set('chroot', $this->projectDir . '/public');

        $dompdf = new Dompdf($options);

        $logoRealPath = realpath($this->projectDir . '/public/images/logo.jpg');
        $signatureRealPath = realpath($this->projectDir . '/public/images/signature.jpg');

        if (!$logoRealPath || !$signatureRealPath) {
            throw new \RuntimeException('Logo ou signature introuvable.');
        }

        $logoPath = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoRealPath));
        $signaturePath = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($signatureRealPath));
//      dd($logoPath, file_exists($logoPath), $signaturePath, file_exists($signaturePath));

        $receiptNumber = 'SOSFB-' . date('Y') . '-' . str_pad(
                (string) $donation->getId(),
                6,
                '0',
                STR_PAD_LEFT
            );

        $html = $this->twig->render('donation/pdf/receipt.html.twig', [
            'donation' => $donation,
            'logoPath' => $logoPath,
            'signaturePath' => $signaturePath,
            'receiptNumber' => $receiptNumber,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $directory = $this->projectDir . '/var/receipts';

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = 'recu-don-' . $receiptNumber . '.pdf';
        $path = $directory . '/' . $filename;

        file_put_contents($path, $dompdf->output());
        $donation->setFiscalReceiptNumber($receiptNumber);
        $donation->setReceiptPdfPath($path);
        $donation->setReceiptGeneratedAt(new \DateTimeImmutable());

        return $path;
    }
}
