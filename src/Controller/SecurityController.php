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

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EmailVerifier $emailVerifier,
    ) {
    }

    #[Route(path: '/connexion', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('main_home');
        }

        $user = new User();
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

    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        RoleRepository $roleRepository,
        AuthenticationUtils $authenticationUtils,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('main_home');
        }

        $user = new User();
        $formRegister = $this->createForm(RegistrationFormType::class, $user, [
            'action' => $this->generateUrl('app_register'),
        ]);

        $formRegister->handleRequest($request);

        if ($formRegister->isSubmitted() && $formRegister->isValid()) {
            $user = $formRegister->getData();

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $formRegister->get('plainPassword')->getData()
                )
            );

            $user->setRoles(['ROLE_USER']);
            $user->setActif(true);
            $user->setIsVerified(false);
            $user->setCreationDate(new \DateTime());

            $roleUser = $roleRepository->findOneBy([
                'name' => 'Utilisateur',
            ]);

            $user->setUserRole($roleUser);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('contact@sosfaonsbretagne.fr', 'SOS Faons Bretagne'))
                    ->to((string) $user->getEmail())
                    ->subject('Confirmez votre adresse email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
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

    #[Route(path: '/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException(
            'This method can be blank - it will be intercepted by the logout key on your firewall.'
        );
    }

    #[Route('/verification/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        TranslatorInterface $translator,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            /** @var User $user */
            $user = $this->getUser();

            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash(
                'verify_email_error',
                $translator->trans($exception->getReason(), [], 'VerifyEmailBundle')
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
