<?php

namespace App\Service\ServiceShop;

use App\Entity\PreOrder;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class PreOrderMailerService
{
    public function __construct(
        private MailerInterface $mailer,
    )
    {
    }

    public function sendPreOrderConfirmation(PreOrder $preOrder): void
    {
        $email = (new TemplatedEmail())
            ->from('contact@sosfaonsbretagne.fr')
            ->to($preOrder->getUser()->getEmail())
            ->subject('Votre précommande a bien été reçue')
            ->htmlTemplate('shop/email/pre_order_confirmation.html.twig')
            ->context([
                'preOrder' => $preOrder,
            ]);

        $this->mailer->send($email);
    }

    public function sendPaymentLink(PreOrder $preOrder): void
    {
        $email = (new TemplatedEmail())
            ->from('contact@sosfaonsbretagne.fr')
            ->to($preOrder->getUser()->getEmail())
            ->subject('Votre précommande a été validée')
            ->htmlTemplate('shop/email/pre_order_payment.html.twig')
            ->context([
                'preOrder' => $preOrder,
            ]);

        $this->mailer->send($email);
    }
    public function sendPaymentConfirmation(PreOrder $preOrder, string $pdfPath): void
    {
        $email = (new TemplatedEmail())
            ->from('contact@sosfaonsbretagne.fr')
            ->to($preOrder->getUser()->getEmail())
            ->subject('Paiement confirmé - Votre facture')
            ->htmlTemplate('shop/email/pre_order_payment_confirmation.html.twig')
            ->context([
                'preOrder' => $preOrder,
            ])
            ->attachFromPath($pdfPath);

        $this->mailer->send($email);
    }
    public function sendPreOrderNotificationToAssociation(PreOrder $preOrder): void
    {
        $email = (new TemplatedEmail())
            ->from('contact@sosfaonsbretagne.fr')
            ->to('contact@sosfaonsbretagne.fr')
            ->subject('Nouvelle précommande reçu - SOS Faons Bretagne')
            ->htmlTemplate('shop/email/pre_order_notification.html.twig')
            ->context([
                'preOrder' => $preOrder,
            ]);
        $this->mailer->send($email);
    }

    public function sendPreOrderPaymentConfirmationNotificationToAssociation(PreOrder $preOrder): void
    {
        $email = (new TemplatedEmail())
            ->from('contact@sosfaonsbretagne.fr')
            ->to('contact@sosfaonsbretagne.fr')
            ->subject('Nouvelle précommande reçu - SOS Faons Bretagne')
            ->htmlTemplate('shop/email/pre_order_payment_notification.html.twig')
            ->context([
                'preOrder' => $preOrder,
            ]);
        $this->mailer->send($email);
    }
}
