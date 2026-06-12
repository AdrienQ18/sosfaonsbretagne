<?php

namespace App\Service\Shop;

use App\Entity\PreOrder;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

/**
 * Service de génération des factures PDF des précommandes.
 *
 * Ce service utilise Dompdf pour générer une facture PDF
 * à partir d'un template Twig.
 *
 * La facture générée est enregistrée dans le dossier var/invoice.
 */
class PreOrderPdfService
{
    public function __construct(
        private Environment $twig,
        private string $projectDir,
    ) {
    }

    /**
     * Génère la facture PDF d'une précommande.
     *
     * Si la facture existe déjà, elle est réutilisée afin
     * d'éviter de générer plusieurs factures pour une même précommande.
     *
     * @param PreOrder $preOrder Précommande concernée
     *
     * @return string Chemin complet du PDF généré
     */
    public function generateInvoice(PreOrder $preOrder): string
    {
        // Si une facture existe déjà pour cette précommande, on retourne son chemin.
        $existingPath = $preOrder->getInvoicePdfPath();

        if ($existingPath && is_file($existingPath)) {
            return $existingPath;
        }

        // Configuration de Dompdf.
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        // Récupération des images utilisées dans la facture.
        $logoRealPath = realpath($this->projectDir . '/public/images/logo.jpg');
        $signatureRealPath = realpath($this->projectDir . '/public/images/signature.jpg');

        if (!$logoRealPath || !$signatureRealPath) {
            throw new \RuntimeException('Logo ou signature introuvable.');
        }

        /**
         * Conversion des images en base64.
         *
         * Cela permet à Dompdf d'intégrer les images directement
         * dans le PDF sans dépendre d'une URL publique.
         */
        $logoPath = 'data:image/jpeg;base64,'
            . base64_encode(file_get_contents($logoRealPath));

        $signaturePath = 'data:image/jpeg;base64,'
            . base64_encode(file_get_contents($signatureRealPath));

        $dompdf = new Dompdf($options);

        /**
         * Génération d'un numéro de facture unique.
         *
         * Exemple :
         * precommande-SOSFB-2026-000001
         */
        $invoiceNumber = 'precommande-SOSFB-' . date('Y') . '-' . str_pad(
                (string) $preOrder->getId(),
                6,
                '0',
                STR_PAD_LEFT
            );

        // Génération du HTML de la facture à partir du template Twig.
        $html = $this->twig->render(
            'pdf/shop/facture_Precommande_Pdf_acquittee.html.twig',
            [
                'preOrder' => $preOrder,
                'logoPath' => $logoPath,
                'signaturePath' => $signaturePath,
                'invoiceNumber' => $invoiceNumber,
            ]
        );

        // Chargement du HTML dans Dompdf puis génération du PDF.
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Dossier privé dans lequel les factures sont stockées.
        $directory = $this->projectDir . '/var/invoice';

        // Création du dossier s'il n'existe pas.
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(
                sprintf('Impossible de créer le dossier %s', $directory)
            );
        }

        $filename = 'Facture-' . $invoiceNumber . '.pdf';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        // Écriture du fichier PDF sur le serveur.
        $bytes = file_put_contents($path, $dompdf->output());

        if ($bytes === false || !is_file($path)) {
            throw new \RuntimeException(
                sprintf('Écriture du PDF impossible : %s', $path)
            );
        }

        // Mise à jour des informations de facture sur la précommande.
        $preOrder->setInvoiceReceiptNumber($invoiceNumber);
        $preOrder->setInvoicePdfPath($path);
        $preOrder->setInvoiceGeneratedAt(new \DateTimeImmutable());

        return $path;
    }
}
