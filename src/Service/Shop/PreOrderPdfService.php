<?php

namespace App\Service\Shop;

use App\Entity\PreOrder;
use Twig\Environment;
use Dompdf\Dompdf;
use Dompdf\Options;

class PreOrderPdfService
{
    public function __construct(
        private Environment $twig,
        private string      $projectDir,
    )
    {
    }

    public function generateInvoice(PreOrder $preOrder): string
    {

        $existingPath = $preOrder->getInvoicePdfPath();
        if ($existingPath && is_file($existingPath)) {
            return $existingPath;
        }
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $logoRealPath = realpath($this->projectDir . '/public/images/logo.jpg');
        $signatureRealPath = realpath($this->projectDir . '/public/images/signature.jpg');

        if (!$logoRealPath || !$signatureRealPath) {
            throw new \RuntimeException('Logo ou signature introuvable.');
        }

        $logoPath = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoRealPath));
        $signaturePath = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($signatureRealPath));

        $dompdf = new Dompdf($options);
        $invoiceNumber = 'precommande-SOSFB-' . date('Y') . '-' . str_pad((string)$preOrder->getId(), 6, '0', STR_PAD_LEFT);
        $html = $this->twig->render(
            'pdf/shop/facture_Precommande_Pdf_acquittee.html.twig', [
            'preOrder' => $preOrder,
            'logoPath' => $logoPath,
            'signaturePath' => $signaturePath,
            'invoiceNumber' => $invoiceNumber,
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $directory = $this->projectDir . '/var/invoice';

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Impossible de creer le dossier %s', $directory));
        }

        $filename = 'Facture-' . $invoiceNumber . '.pdf';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        $bytes = file_put_contents($path, $dompdf->output());
        if ($bytes === false || !is_file($path)) {
            throw new \RuntimeException(sprintf('Ecriture du PDF impossible : %s', $path));
        }

        $preOrder->setInvoiceReceiptNumber($invoiceNumber);
        $preOrder->setInvoicePdfPath($path);
        $preOrder->setInvoiceGeneratedAt(new \DateTimeImmutable());

        return $path;
    }

}
