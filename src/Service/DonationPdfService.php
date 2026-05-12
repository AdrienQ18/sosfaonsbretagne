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

    /**
     * Génère le reçu fiscal PDF d'une donation validée.
     *
     * Le PDF est enregistré dans /var/receipts.
     * La donation est mise à jour avec :
     * - le numéro du reçu fiscal
     * - le chemin du PDF
     * - la date de génération.
     */
    public function generateFiscalReceipt(Donation $donation): string
    {
        /*
         * Sécurité :
         * Si un reçu existe déjà, on retourne simplement son chemin.
         * Cela évite de générer plusieurs reçus fiscaux pour le même don.
         */
        if ($donation->getReceiptPdfPath()) {
            return $donation->getReceiptPdfPath();
        }

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', $this->projectDir . '/public');

        $dompdf = new Dompdf($options);

        /*
         * Les images sont placées dans /public/images.
         * Grâce au chroot, Dompdf peut les lire via un chemin relatif.
         */
        $logoPath = 'images/logo.png';
        $signaturePath = 'images/signature.png';

        /*
         * Numéro unique du reçu fiscal.
         * Exemple : SOSFB-2026-000033
         */
        $receiptNumber = 'SOSFB-' . date('Y') . '-' . str_pad(
                (string) $donation->getId(),
                6,
                '0',
                STR_PAD_LEFT
            );

        /*
         * Génération du HTML utilisé par Dompdf.
         */
        $html = $this->twig->render('donation/pdf/receipt.html.twig', [
            'donation' => $donation,
            'logoPath' => $logoPath,
            'signaturePath' => $signaturePath,
            'receiptNumber' => $receiptNumber,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        /*
         * Dossier privé de stockage des reçus.
         * On évite /public car le PDF contient des données personnelles.
         */
        $directory = $this->projectDir . '/var/receipts';

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = 'recu-don-' . $receiptNumber . '.pdf';
        $path = $directory . '/' . $filename;

        file_put_contents($path, $dompdf->output());

        /*
         * Mise à jour de la donation avec les informations du reçu fiscal.
         * Le flush est volontairement fait dans le service appelant.
         */
        $donation->setFiscalReceiptNumber($receiptNumber);
        $donation->setReceiptPdfPath($path);
        $donation->setReceiptGeneratedAt(new \DateTimeImmutable());

        return $path;
    }
}
