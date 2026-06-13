<?php

namespace App\Service\Signalement;

use App\Entity\Alert;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Service d'envoi des emails liés aux signalements.
 *
 * Ce service gère :
 * - la notification de l'association lors d'un nouveau signalement ;
 * - la notification de validation du signalement ;
 * - la notification de refus du signalement.
 */
final class AlertMailerService
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    /**
     * Informe l'association qu'un nouveau signalement a été créé.
     *
     * @param Alert $alert Signalement concerné
     */
    public function sendAdminNotification(Alert $alert): void
    {
        // L'email part dès la création du signalement pour permettre
        // à l'association de réagir rapidement.
        // Notification interne envoyée à l'association.
        $email = (new TemplatedEmail())
            ->from(
                new Address(
                    'contact@sosfaonsbretagne.fr',
                    'SOS Faons Bretagne'
                )
            )
            ->to('contact@sosfaonsbretagne.fr')

            // Le reply-to permet aux membres de l'association
            // de répondre directement à l'auteur du signalement
            // sans avoir à copier son adresse email.
            ->replyTo(
                $alert->getUser()?->getEmail()
                ?? 'contact@sosfaonsbretagne.fr'
            )

            ->subject('[Signalement] ' . $alert->getType())
            ->htmlTemplate(
                'emails/signalement/alert_admin_notification.html.twig'
            )
            ->context([
                'alert' => $alert,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Informe l'utilisateur que son signalement a été validé.
     *
     * @param Alert $alert Signalement concerné
     */
    public function sendValidatedNotification(Alert $alert): void
    {
        $recipient = $alert->getUser()?->getEmail();

        // Les signalements doivent normalement être liés à un utilisateur,
        // mais cette garde évite une erreur d'envoi sur des données anciennes.
        // Aucun email à envoyer si aucun utilisateur n'est associé.
        if (!$recipient) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from('contact@sosfaonsbretagne.fr')
            ->to($recipient)
            ->subject(
                'Votre signalement a été validé - SOS Faons Bretagne'
            )
            ->htmlTemplate(
                'emails/signalement/alert_validated_user.html.twig'
            )
            ->context([
                'alert' => $alert,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Informe l'utilisateur que son signalement a été refusé.
     *
     * @param Alert $alert Signalement concerné
     */
    public function sendRefusedNotification(Alert $alert): void
    {
        $recipient = $alert->getUser()?->getEmail();

        // Même garde que pour la validation : pas de destinataire, pas d'email.
        // Aucun email à envoyer si aucun utilisateur n'est associé.
        if (!$recipient) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from('contact@sosfaonsbretagne.fr')
            ->to($recipient)
            ->subject(
                'Votre signalement a été refusé - SOS Faons Bretagne'
            )
            ->htmlTemplate(
                'emails/signalement/alert_refused_user.html.twig'
            )
            ->context([
                'alert' => $alert,
            ]);

        $this->mailer->send($email);
    }
}
