<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\RoleRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

/**
 * Contrôleur de sécurité.
 *
 * Il gère :
 * - la connexion ;
 * - l'inscription ;
 * - la déconnexion ;
 * - la vérification de l'adresse email.
 */
class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
    ) {
    }

    /**
     * Affiche la page de connexion.
     *
     * Si l'utilisateur est déjà connecté,
     * il est redirigé vers la page d'accueil.
     */
    #[Route(path: '/connexion', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
    ): Response {
        // Redirection si l'utilisateur est déjà connecté.
        if ($this->getUser()) {
            return $this->redirectToRoute('main_home');
        }

        $user = new User();

        // Création du formulaire d'inscription affiché sur la même page que la connexion.
        $formRegister = $this->createForm(RegistrationFormType::class, $user, [
            'action' => $this->generateUrl('app_register'),
        ]);

        return $this->render('security/login.html.twig', [
            'mode' => 'login',
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'formRegister' => $formRegister->createView(),
        ]);
    }

    /**
     * Affiche et traite le formulaire d'inscription.
     *
     * Lors de la création du compte :
     * - le mot de passe est hashé ;
     * - le rôle Symfony ROLE_USER est ajouté ;
     * - le rôle métier "Utilisateur" est associé ;
     * - un email de confirmation est envoyé.
     */
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        RoleRepository $roleRepository,
        AuthenticationUtils $authenticationUtils,
    ): Response {
        // Redirection si l'utilisateur est déjà connecté.
        if ($this->getUser()) {
            return $this->redirectToRoute('main_home');
        }

        $user = new User();

        $formRegister = $this->createForm(RegistrationFormType::class, $user, [
            'action' => $this->generateUrl('app_register'),
        ]);

        $formRegister->handleRequest($request);

        if ($formRegister->isSubmitted() && $formRegister->isValid()) {
            /** @var User $user */
            $user = $formRegister->getData();

            // Hash du mot de passe avant enregistrement en base de données.
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $formRegister->get('plainPassword')->getData()
                )
            );

            // Initialisation des informations par défaut du compte.
            $user->setRoles(['ROLE_USER']);
            $user->setActif(true);
            $user->setIsVerified(false);
            $user->setCreationDate(new \DateTime());

            // Attribution du rôle métier "Utilisateur".
            $roleUser = $roleRepository->findOneBy([
                'name' => 'Utilisateur',
            ]);

            // Ce rôle métier sert à l'affichage et aux formulaires côté site,
            // en complément du rôle Symfony utilisé pour la sécurité.
            $user->setUserRole($roleUser);

            $entityManager->persist($user);
            $entityManager->flush();

            // Envoi de l'email de confirmation d'adresse email.
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address(
                        'contact@sosfaonsbretagne.fr',
                        'SOS Faons Bretagne'
                    ))
                    ->to((string) $user->getEmail())
                    ->subject('Confirmez votre adresse email')
                    ->htmlTemplate('emails/registration/confirmation_email.html.twig')
            );

            $this->addFlash(
                'success',
                'Votre compte a été créé. Vérifiez votre adresse email avant de vous connecter.'
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/login.html.twig', [
            'mode' => 'register',
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'formRegister' => $formRegister->createView(),
        ]);
    }

    /**
     * Déconnecte l'utilisateur.
     *
     * Cette méthode est interceptée automatiquement
     * par le système de sécurité Symfony.
     */
    #[Route(path: '/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException(
            'This method can be blank - it will be intercepted by the logout key on your firewall.'
        );
    }

    /**
     * Vérifie l'adresse email d'un utilisateur après clic
     * sur le lien reçu par email.
     */
    #[Route('/verification/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        TranslatorInterface $translator,
    ): Response {
        // L'utilisateur doit être connecté pour valider son adresse email.
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            /** @var User $user */
            $user = $this->getUser();

            // La signature contient l'identité de l'utilisateur et une date
            // d'expiration : elle évite la validation manuelle d'un autre compte.
            // Validation de la signature présente dans le lien d'email.
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash(
                'verify_email_error',
                $translator->trans(
                    $exception->getReason(),
                    [],
                    'VerifyEmailBundle'
                )
            );

            return $this->redirectToRoute('app_login');
        }

        $this->addFlash(
            'success',
            'Votre adresse email a bien été confirmée. Vous pouvez maintenant vous connecter.'
        );

        return $this->redirectToRoute('app_login');
    }
}
