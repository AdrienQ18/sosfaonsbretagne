<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Authenticator personnalisé pour la connexion des utilisateurs.
 *
 * Il gère :
 * - la récupération de l'email et du mot de passe ;
 * - la vérification du token CSRF ;
 * - l'option "Se souvenir de moi" ;
 * - la redirection après connexion.
 */
class UserAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Crée le Passport utilisé par Symfony pour authentifier l'utilisateur.
     */
    public function authenticate(Request $request): Passport
    {
        // Récupération de l'email envoyé par le formulaire de connexion.
        $email = $request->getPayload()->getString('email');

        // Stocke le dernier email saisi pour le réafficher en cas d'erreur.
        $request->getSession()->set(
            SecurityRequestAttributes::LAST_USERNAME,
            $email
        );

        return new Passport(
            // Identifiant de l'utilisateur.
            new UserBadge($email),

            // Mot de passe saisi dans le formulaire.
            new PasswordCredentials(
                $request->getPayload()->getString('password')
            ),

            [
                // Protection CSRF du formulaire de connexion.
                new CsrfTokenBadge(
                    'authenticate',
                    $request->getPayload()->getString('_csrf_token')
                ),

                // Le badge autorise Symfony à utiliser le remember_me configuré
                // dans security.yaml lorsque la case est cochée.
                new RememberMeBadge(),
            ]
        );
    }

    /**
     * Redirige l'utilisateur après une connexion réussie.
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        /**
         * Si l'utilisateur voulait accéder à une page protégée
         * avant connexion, il est redirigé vers cette page.
         */
        if ($targetPath = $this->getTargetPath(
            $request->getSession(),
            $firewallName
        )) {
            // Respecte l'URL initialement demandée au lieu de renvoyer
            // systématiquement vers l'accueil.
            return new RedirectResponse($targetPath);
        }

        // Redirection par défaut après connexion.
        return new RedirectResponse(
            $this->urlGenerator->generate('main_home')
        );
    }

    /**
     * Retourne l'URL de la page de connexion.
     */
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
