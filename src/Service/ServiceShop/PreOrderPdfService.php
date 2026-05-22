<?php

namespace App\Service\ServiceShop;

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

        if ($preOrder->getInvoicePdfPath()) {
            return $preOrder->getInvoicePdfPath();
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
        $invoiceNumber = 'SOSFB-Invoice-Paid-PreOrder' . date('Y') . '-' . str_pad((string)$preOrder->getId(), 6, '0', STR_PAD_LEFT);
        $html = $this->twig->render(
            'shop/pdf/invoicePaidPreOrder.html.twig', [
            'preOrder' => $preOrder,
            'logoPath' => $logoPath,
            'signaturePath' => $signaturePath,
            'invoiceNumber' => $invoiceNumber,
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $directory = $this->projectDir . '/var/invoice';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = 'Facture-' . $invoiceNumber . '.pdf';
        $path = $directory . '/' . $filename;
        file_put_contents($path, $dompdf->output());
        $preOrder->setInvoiceReceiptNumber($invoiceNumber);
        $preOrder->setInvoicePdfPath($path);
        $preOrder->setInvoiceGeneratedAt(new \DateTimeImmutable());

        return $path;

    }

}
