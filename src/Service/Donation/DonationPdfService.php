<?php

namespace App\Service\Donation;

use App\Entity\Donation;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

/**
 * Service de génération des reçus fiscaux PDF pour les dons.
 *
 * Ce service utilise Dompdf pour générer un document PDF
 * à partir d'un template Twig.
 *
 * Le reçu généré est ensuite enregistré dans le dossier var/receipts.
 */
final class DonationPdfService
{
    public function __construct(
        private Environment $twig,
        private string $projectDir,
    ) {
    }

    /**
     * Génère le reçu fiscal PDF d'un don.
     *
     * Si le PDF existe déjà, il est réutilisé afin d'éviter
     * de générer plusieurs reçus fiscaux pour le même don.
     *
     * @param Donation $donation Don concerné
     *
     * @return string Chemin complet du PDF généré
     */
    public function generateFiscalReceipt(Donation $donation): string
    {
        // Si un reçu fiscal existe déjà pour ce don, on retourne son chemin.
        // Cela évite de produire deux numéros de reçu pour le même paiement.
        $existingPath = $donation->getReceiptPdfPath();

        if ($existingPath && is_file($existingPath)) {
            return $existingPath;
        }

        // Configuration de Dompdf.
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);

        // Récupération des fichiers image utilisés dans le PDF.
        $logoRealPath = realpath($this->projectDir . '/public/images/logo.jpg');
        $signatureRealPath = realpath($this->projectDir . '/public/images/signature.jpg');

        if (!$logoRealPath || !$signatureRealPath) {
            throw new \RuntimeException('Logo ou signature introuvable.');
        }

        /**
         * Conversion des images en base64.
         *
         * Cela permet à Dompdf d'intégrer correctement les images
         * dans le PDF, même sans accès direct au chemin public.
         */
        $logoPath = 'data:image/jpeg;base64,'
            . base64_encode(file_get_contents($logoRealPath));

        $signaturePath = 'data:image/jpeg;base64,'
            . base64_encode(file_get_contents($signatureRealPath));

        /**
         * Génération d'un numéro de reçu fiscal unique.
         *
         * Exemple :
         * don-SOSFB-2026-000001
         */
        $receiptNumber = 'don-SOSFB-' . date('Y') . '-' . str_pad(
                (string) $donation->getId(),
                6,
                '0',
                STR_PAD_LEFT
            );

        // Génération du HTML du PDF à partir du template Twig.
        $html = $this->twig->render('pdf/donation/recu_Fiscale_Pdf_Donation.html.twig', [
            'donation' => $donation,
            'logoPath' => $logoPath,
            'signaturePath' => $signaturePath,
            'receiptNumber' => $receiptNumber,
        ]);

        // Chargement du HTML dans Dompdf.
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Dossier privé dans lequel les reçus fiscaux sont stockés.
        // Le dossier var/ n'est pas exposé directement par le serveur web.
        $directory = $this->projectDir . '/var/receipts';

        // Création du dossier s'il n'existe pas.
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(
                sprintf('Impossible de créer le dossier %s', $directory)
            );
        }

        $filename = 'Recu-fiscal-' . $receiptNumber . '.pdf';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        // Écriture du fichier PDF sur le serveur.
        $bytes = file_put_contents($path, $dompdf->output());

        if ($bytes === false || !is_file($path)) {
            throw new \RuntimeException(
                sprintf('Écriture du PDF impossible : %s', $path)
            );
        }

        // Mise à jour des informations du don liées au reçu fiscal.
        // Le flush reste volontairement dans le service appelant pour garder
        // une transaction cohérente avec l'envoi des emails.
        $donation->setFiscalReceiptNumber($receiptNumber);
        $donation->setReceiptPdfPath($path);
        $donation->setReceiptGeneratedAt(new \DateTimeImmutable());

        return $path;
    }
}
