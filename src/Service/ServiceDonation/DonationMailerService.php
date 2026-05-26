<?php

namespace App\Service\ServiceDonation;

use App\Entity\Donation;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class DonationMailerService

{
    public function __construct(
        private MailerInterface $mailer,
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendFiscalReceipt(Donation $donation, string $pdfPath): void
    {
        $email = (new TemplatedEmail())
            ->from('contact@sosfaonsbretagne.fr')
            ->to($donation->getEmail())
            ->subject('Votre reçu fiscal - SOS Faons Bretagne')
            ->htmlTemplate('donation/email/donation_receipt.html.twig')
            ->context([
                'donation' => $donation,
            ])
            ->attachFromPath($pdfPath);
        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendDonationNotificationToAssociation(Donation $donation): void
    {
        $email = (new TemplatedEmail())
            ->from('contact@sosfaonsbretagne.fr')
            ->to('contact@sosfaonsbretagne.fr')
            ->subject('Nouveau don reçu - SOS Faons Bretagne')
            ->htmlTemplate('donation/email/donation_notification.html.twig')
            ->context([
                'donation' => $donation,
            ]);
        $this->mailer->send($email);
    }
}
