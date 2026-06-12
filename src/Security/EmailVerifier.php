<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Service de gestion de la vérification des adresses email.
 *
 * Ce service permet :
 * - de générer un lien sécurisé de validation ;
 * - d'envoyer l'email de confirmation ;
 * - de valider l'adresse email de l'utilisateur.
 */
class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Génère un lien de confirmation sécurisé
     * puis envoie l'email de validation à l'utilisateur.
     *
     * @param string $verifyEmailRouteName Route de validation
     * @param User $user Utilisateur concerné
     * @param TemplatedEmail $email Email à envoyer
     */
    public function sendEmailConfirmation(
        string $verifyEmailRouteName,
        User $user,
        TemplatedEmail $email
    ): void {
        /**
         * Génération d'une URL signée.
         *
         * Cette URL contient une signature cryptographique
         * empêchant toute modification frauduleuse du lien.
         */
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            (string) $user->getId(),
            (string) $user->getEmail()
        );

        // Récupération du contexte Twig existant.
        $context = $email->getContext();

        // Ajout des informations nécessaires au template.
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);

        // Envoi de l'email de confirmation.
        $this->mailer->send($email);
    }

    /**
     * Valide l'adresse email d'un utilisateur.
     *
     * Vérifie la signature du lien reçu puis
     * active le compte utilisateur.
     *
     * @param Request $request Requête contenant le lien signé
     * @param User $user Utilisateur à vérifier
     *
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(
        Request $request,
        User $user
    ): void {
        /**
         * Vérification de la signature du lien.
         *
         * Une exception sera levée si :
         * - le lien a été modifié ;
         * - le lien a expiré ;
         * - le lien est invalide.
         */
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
            $request,
            (string) $user->getId(),
            (string) $user->getEmail()
        );

        // Activation définitive du compte utilisateur.
        $user->setIsVerified(true);

        // Sauvegarde en base de données.
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
