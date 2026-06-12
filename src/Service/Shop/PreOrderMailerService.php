<?php

namespace App\Service\Shop;

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
            ->htmlTemplate('emails/shop/pre_order_created_user.html.twig')
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
            ->htmlTemplate('emails/shop/pre_order_payment_link.html.twig')
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
            ->htmlTemplate('emails/shop/pre_order_paid_user.html.twig')
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
            ->replyTo($preOrder->getUser()?->getEmail() ?? 'contact@sosfaonsbretagne.fr')
            ->subject('Nouvelle précommande reçu - SOS Faons Bretagne')
            ->htmlTemplate('emails/shop/pre_order_created_admin.html.twig')
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
            ->replyTo($preOrder->getUser()?->getEmail() ?? 'contact@sosfaonsbretagne.fr')
            ->subject('Nouvelle précommande reçu - SOS Faons Bretagne')
            ->htmlTemplate('emails/shop/pre_order_paid_admin.html.twig')
            ->context([
                'preOrder' => $preOrder,
            ]);
        $this->mailer->send($email);
    }
}
