<?php

namespace App\Service\Donation;

use App\Entity\Donation;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Service d'envoi des emails liés aux dons.
 *
 * Ce service est responsable :
 * - de l'envoi des reçus fiscaux aux donateurs ;
 * - de l'envoi des notifications de nouveaux dons à l'association.
 */
class DonationMailerService
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    /**
     * Envoie le reçu fiscal au donateur.
     *
     * Le reçu fiscal PDF est joint à l'email.
     *
     * @param Donation $donation Don concerné
     * @param string $pdfPath Chemin du reçu fiscal PDF
     *
     * @throws TransportExceptionInterface
     */
    public function sendFiscalReceipt(
        Donation $donation,
        string $pdfPath
    ): void {
        // Email envoyé au donateur avec son reçu fiscal en pièce jointe.
        $email = (new TemplatedEmail())
            ->from(
                new Address(
                    'contact@sosfaonsbretagne.fr',
                    'SOS Faons Bretagne'
                )
            )
            ->to($donation->getEmail())
            ->subject('Votre reçu fiscal - SOS Faons Bretagne')
            ->htmlTemplate('emails/donation/donation_fiscal_receipt.html.twig')
            ->context([
                'donation' => $donation,
            ])
            ->attachFromPath($pdfPath);

        $this->mailer->send($email);
    }

    /**
     * Informe l'association qu'un nouveau don a été reçu.
     *
     * Le reply-to est configuré avec l'adresse email
     * du donateur lorsqu'il possède un compte utilisateur.
     *
     * @param Donation $donation Don concerné
     *
     * @throws TransportExceptionInterface
     */
    public function sendDonationNotificationToAssociation(
        Donation $donation
    ): void {
        // Notification interne envoyée à l'association.
        $email = (new TemplatedEmail())
            ->from(
                new Address(
                    'contact@sosfaonsbretagne.fr',
                    'SOS Faons Bretagne'
                )
            )
            ->to('contact@sosfaonsbretagne.fr')

            // Permet de répondre directement au donateur depuis le client mail.
            ->replyTo(
                $donation->getUser()?->getEmail()
                ?? 'contact@sosfaonsbretagne.fr'
            )

            ->subject('Nouveau don reçu - SOS Faons Bretagne')
            ->htmlTemplate('emails/donation/donation_notification.html.twig')
            ->context([
                'donation' => $donation,
            ]);

        $this->mailer->send($email);
    }
}
