<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * Contrôleur de réinitialisation du mot de passe.
 *
 * Il gère :
 * - la demande de réinitialisation ;
 * - l'envoi de l'email contenant le lien sécurisé ;
 * - la vérification du token ;
 * - la modification du mot de passe ;
 * - l'envoi d'un email de confirmation après changement.
 */
#[Route('/reinitialiser-mot-de-passe')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Affiche et traite le formulaire de demande de réinitialisation.
     */
    #[Route('', name: 'app_forgot_password_request')]
    public function request(
        Request $request,
        MailerInterface $mailer,
        TranslatorInterface $translator
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $email */
            $email = $form->get('email')->getData();

            return $this->processSendingPasswordResetEmail(
                $email,
                $mailer,
                $translator
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    /**
     * Affiche la page de confirmation après demande de réinitialisation.
     *
     * Cette page est affichée même si l'adresse email n'existe pas,
     * afin d'éviter de révéler si un compte est inscrit ou non.
     */
    #[Route('/verification-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Si aucun token réel n'existe en session, on génère un faux token.
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Vérifie le token de réinitialisation et permet de définir un nouveau mot de passe.
     */
    #[Route('/reinitialiser/{token}', name: 'app_reset_password')]
    public function reset(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator,
        MailerInterface $mailer,
        ?string $token = null
    ): Response {
        // Si un token est présent dans l'URL, il est stocké en session.
        if ($token) {
            // Le token est retiré de l'URL après stockage pour limiter son
            // exposition dans l'historique navigateur et les éventuels logs.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        // Récupération du token depuis la session.
        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw $this->createNotFoundException(
                'Aucun jeton de réinitialisation de mot de passe n’a été trouvé.'
            );
        }

        try {
            // Cette validation récupère l'utilisateur uniquement si le token est
            // authentique, non expiré et encore associé à une demande active.
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash(
                'reset_password_error',
                sprintf(
                    '%s - %s',
                    $translator->trans(
                        ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE,
                        [],
                        'ResetPasswordBundle'
                    ),
                    $translator->trans(
                        $e->getReason(),
                        [],
                        'ResetPasswordBundle'
                    )
                )
            );

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Suppression de la demande de réinitialisation pour empêcher la réutilisation du lien.
            $this->resetPasswordHelper->removeResetRequest($token);

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Hash du nouveau mot de passe avant enregistrement.
            $user->setPassword(
                $passwordHasher->hashPassword($user, $plainPassword)
            );

            $this->entityManager->flush();

            // Envoi d'un email informant l'utilisateur que son mot de passe a été modifié.
            $this->sendPasswordChangedEmail($mailer, $user);

            // Nettoyage de la session après réinitialisation.
            $this->cleanSessionAfterReset();

            $this->addFlash(
                'success',
                'Votre mot de passe a bien été modifié.'
            );

            return $this->redirectToRoute('main_home');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }

    /**
     * Génère un token de réinitialisation et envoie l'email associé.
     *
     * Si l'utilisateur n'existe pas, la redirection reste identique
     * pour ne pas indiquer si l'adresse email est connue du site.
     */
    private function processSendingPasswordResetEmail(
        string $emailFormData,
        MailerInterface $mailer,
        TranslatorInterface $translator
    ): RedirectResponse {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Même comportement si l'email n'existe pas : protection contre l'énumération des comptes.
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            // Génération du token sécurisé de réinitialisation.
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // Même redirection en cas d'erreur pour conserver le même comportement
            // vis-à-vis de l'utilisateur et ne pas exposer l'état du compte.
            return $this->redirectToRoute('app_check_email');
        }

        // Email contenant le lien de réinitialisation.
        $email = (new TemplatedEmail())
            ->from(new Address(
                'contact@sosfaonsbretagne.fr',
                'SOS Faons Bretagne'
            ))
            ->to((string) $user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('reset_password/password_reset_email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ]);

        $mailer->send($email);

        // Stockage du token en session pour l'affichage de la page de confirmation.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }

    /**
     * Envoie un email de confirmation après modification du mot de passe.
     */
    private function sendPasswordChangedEmail(
        MailerInterface $mailer,
        User $user
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address(
                'contact@sosfaonsbretagne.fr',
                'SOS Faons Bretagne'
            ))
            ->to((string) $user->getEmail())
            ->subject('Votre mot de passe a été modifié')
            ->htmlTemplate('emails/password_changed_notification.html.twig')
            ->context([
                'user' => $user,
            ]);

        $mailer->send($email);
    }
}
