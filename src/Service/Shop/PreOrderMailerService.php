<?php

namespace App\Service\Shop;

use App\Entity\PreOrder;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Service d'envoi des emails liés aux précommandes.
 *
 * Ce service gère :
 * - la confirmation de création de précommande ;
 * - l'envoi du lien de paiement ;
 * - l'envoi de la facture après paiement ;
 * - les notifications internes envoyées à l'association.
 */
class PreOrderMailerService
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    /**
     * Envoie un email à l'utilisateur après création de sa précommande.
     */
    public function sendPreOrderConfirmation(PreOrder $preOrder): void
    {
        // Email de confirmation envoyé au client.
        $email = (new TemplatedEmail())
            ->from(
                new Address(
                    'contact@sosfaonsbretagne.fr',
                    'SOS Faons Bretagne'
                )
            )
            ->to($preOrder->getUser()->getEmail())
            ->subject('Votre précommande a bien été reçue')
            ->htmlTemplate('emails/shop/pre_order_created_user.html.twig')
            ->context([
                'preOrder' => $preOrder,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie le lien de paiement à l'utilisateur
     * lorsque sa précommande est validée par l'association.
     */
    public function sendPaymentLink(PreOrder $preOrder): void
    {
        // Email envoyé après validation administrative de la précommande.
        $email = (new TemplatedEmail())
            ->from(
                new Address(
                    'contact@sosfaonsbretagne.fr',
                    'SOS Faons Bretagne'
                )
            )
            ->to($preOrder->getUser()->getEmail())
            ->subject('Votre précommande a été validée')
            ->htmlTemplate('emails/shop/pre_order_payment_link.html.twig')
            ->context([
                'preOrder' => $preOrder,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie la confirmation de paiement avec la facture en pièce jointe.
     */
    public function sendPaymentConfirmation(
        PreOrder $preOrder,
        string $pdfPath
    ): void {
        // Email envoyé après paiement confirmé par HelloAsso.
        $email = (new TemplatedEmail())
            ->from(
                new Address(
                    'contact@sosfaonsbretagne.fr',
                    'SOS Faons Bretagne'
                )
            )
            ->to($preOrder->getUser()->getEmail())
            ->subject('Paiement confirmé - Votre facture')
            ->htmlTemplate('emails/shop/pre_order_paid_user.html.twig')
            ->context([
                'preOrder' => $preOrder,
            ])
            ->attachFromPath($pdfPath);

        $this->mailer->send($email);
    }

    /**
     * Informe l'association qu'une nouvelle précommande a été créée.
     */
    public function sendPreOrderNotificationToAssociation(
        PreOrder $preOrder
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

            // Permet à l'association de répondre directement au client.
            ->replyTo(
                $preOrder->getUser()?->getEmail()
                ?? 'contact@sosfaonsbretagne.fr'
            )

            ->subject('Nouvelle précommande reçue - SOS Faons Bretagne')
            ->htmlTemplate('emails/shop/pre_order_created_admin.html.twig')
            ->context([
                'preOrder' => $preOrder,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Informe l'association qu'une précommande vient d'être payée.
     */
    public function sendPreOrderPaymentConfirmationNotificationToAssociation(
        PreOrder $preOrder
    ): void {
        // Notification interne envoyée après confirmation du paiement.
        $email = (new TemplatedEmail())
            ->from(
                new Address(
                    'contact@sosfaonsbretagne.fr',
                    'SOS Faons Bretagne'
                )
            )
            ->to('contact@sosfaonsbretagne.fr')

            // Permet à l'association de répondre directement au client.
            ->replyTo(
                $preOrder->getUser()?->getEmail()
                ?? 'contact@sosfaonsbretagne.fr'
            )

            ->subject('Précommande payée - SOS Faons Bretagne')
            ->htmlTemplate('emails/shop/pre_order_paid_admin.html.twig')
            ->context([
                'preOrder' => $preOrder,
            ]);

        $this->mailer->send($email);
    }
}
