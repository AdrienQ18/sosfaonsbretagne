<?php

namespace App\Repository;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\Repository\ResetPasswordRequestRepositoryTrait;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;

/**
 * Repository des demandes de réinitialisation de mot de passe.
 *
 * Ce repository est utilisé par le bundle SymfonyCasts ResetPasswordBundle
 * pour :
 * - créer les demandes de réinitialisation ;
 * - retrouver les jetons de réinitialisation ;
 * - supprimer les demandes expirées ;
 * - gérer le cycle de vie des tokens.
 *
 * @extends ServiceEntityRepository<ResetPasswordRequest>
 */
class ResetPasswordRequestRepository extends ServiceEntityRepository implements ResetPasswordRequestRepositoryInterface
{
    /**
     * Fournit les méthodes nécessaires au fonctionnement
     * du ResetPasswordBundle.
     */
    use ResetPasswordRequestRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordRequest::class);
    }

    /**
     * Crée une nouvelle demande de réinitialisation de mot de passe.
     *
     * Cette méthode est appelée automatiquement par le
     * ResetPasswordBundle lors de la génération d'un lien
     * de réinitialisation.
     *
     * @param User $user Utilisateur concerné par la demande
     * @param \DateTimeInterface $expiresAt Date d'expiration du token
     * @param string $selector Identifiant public du token
     * @param string $hashedToken Version hashée du token stockée en base
     *
     * @return ResetPasswordRequestInterface
     */
    public function createResetPasswordRequest(
        object $user,
        \DateTimeInterface $expiresAt,
        string $selector,
        string $hashedToken
    ): ResetPasswordRequestInterface {
        // Création de l'entité contenant les informations
        // de réinitialisation du mot de passe.
        return new ResetPasswordRequest(
            $user,
            $expiresAt,
            $selector,
            $hashedToken
        );
    }
}
