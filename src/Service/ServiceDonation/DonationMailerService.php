<?php

namespace App\Service\ServiceDonation;

use App\Entity\Donation;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class DonationMailerService

{
    public function __construct(
        private MailerInterface $mailer,
    )
    {
    }

    public function sendFiscalReceipt(Donation $donation, string $pdfPath): void
    {
        $email = (new TemplatedEmail())
            ->from('contact@sosfaonsbretagne.fr')
            ->to($donation->getEmail())
            ->subject('Votre reçu fiscal - SOS Faons Bretagne')
            ->htmlTemplate('donation/email/donation_receipt.html.twig')
            ->context([
                'donation' => $donation,
            ]);

        $this->mailer->send($email);
    }
}
