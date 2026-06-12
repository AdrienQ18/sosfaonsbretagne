<?php

namespace App\Service\Signalement;

use App\Entity\Alert;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class AlertMailerService
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function sendAdminNotification(Alert $alert): void
    {
        $email = (new TemplatedEmail())
            ->from('contact@sosfaonsbretagne.fr')
            ->to('contact@sosfaonsbretagne.fr')
            ->replyTo(
                $alert->getUser()?->getEmail()
                ?? 'contact@sosfaonsbretagne.fr'
            )
            ->subject('[Signalement] ' . $alert->getType())
            ->htmlTemplate('emails/signalement/alert_admin_notification.html.twig')
            ->context([
                'alert' => $alert,
            ]);

        $this->mailer->send($email);
    }

    public function sendValidatedNotification(Alert $alert): void
    {
        $recipient = $alert->getUser()?->getEmail();

        if (!$recipient) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from('contact@sosfaonsbretagne.fr')
            ->to($recipient)
            ->subject('Votre signalement a été validé - SOS Faons Bretagne')
            ->htmlTemplate('emails/signalement/alert_validated_user.html.twig')
            ->context([
                'alert' => $alert,
            ]);

        $this->mailer->send($email);
    }

    public function sendRefusedNotification(Alert $alert): void
    {
        $recipient = $alert->getUser()?->getEmail();

        if (!$recipient) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from('contact@sosfaonsbretagne.fr')
            ->to($recipient)
            ->subject('Votre signalement a été refusé - SOS Faons Bretagne')
            ->htmlTemplate('emails/signalement/alert_refused_user.html.twig')
            ->context([
                'alert' => $alert,
            ]);

        $this->mailer->send($email);
    }
}
