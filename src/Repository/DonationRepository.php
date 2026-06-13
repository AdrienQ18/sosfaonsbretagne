<?php

namespace App\Repository;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des dons.
 *
 * Centralise les requêtes liées aux dons :
 * - recherche filtrée pour l'administration ;
 * - calcul du montant total des dons validés ;
 * - filtrage par donateur, statut ou période.
 *
 * @extends ServiceEntityRepository<Donation>
 */
class DonationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Donation::class);
    }

    /**
     * Retourne une requête de recherche des dons
     * selon les filtres sélectionnés.
     *
     * @param array<string, mixed> $filters
     *
     * @return QueryBuilder
     */
    public function searchDonations(array $filters): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('d');

        // Application des filtres de recherche.
        $this->applyDonationFilters($queryBuilder, $filters);

        // Affichage des dons les plus récents en premier.
        return $queryBuilder->orderBy('d.donationDate', 'DESC');
    }

    /**
     * Applique les filtres de recherche à une requête Doctrine.
     *
     * Les filtres disponibles sont :
     * - recherche globale ;
     * - type de donateur ;
     * - statut ;
     * - email ;
     * - SIRET ;
     * - date de début ;
     * - date de fin.
     *
     * @param QueryBuilder $queryBuilder
     * @param array<string, mixed> $filters
     */
    private function applyDonationFilters(
        QueryBuilder $queryBuilder,
        array $filters
    ): void {
        /**
         * Recherche globale sur plusieurs champs.
         *
         * Permet notamment de rechercher :
         * - prénom ;
         * - nom ;
         * - email ;
         * - raison sociale ;
         * - SIRET ;
         * - numéro de reçu fiscal.
         */
        if (!empty($filters['search'])) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        'd.firstname LIKE :search',
                        'd.lastname LIKE :search',
                        'd.email LIKE :search',
                        'd.companyName LIKE :search',
                        'd.companySiret LIKE :search',
                        'd.fiscalReceiptNumber LIKE :search'
                    )
                )
                ->setParameter(
                    'search',
                    '%' . trim($filters['search']) . '%'
                );
        }

        // Filtre sur le type de donateur (particulier ou entreprise).
        if (isset($filters['donorType']) && $filters['donorType'] !== '') {
            $queryBuilder
                ->andWhere('d.donorType = :donorType')
                ->setParameter(
                    'donorType',
                    $filters['donorType']
                );
        }

        // Filtre sur le statut du don.
        if (isset($filters['status']) && $filters['status'] !== '') {
            $queryBuilder
                ->andWhere('d.status = :status')
                ->setParameter(
                    'status',
                    $filters['status']
                );
        }

        // Recherche spécifique sur l'adresse email.
        if (!empty($filters['email'])) {
            $queryBuilder
                ->andWhere('d.email LIKE :email')
                ->setParameter(
                    'email',
                    '%' . trim($filters['email']) . '%'
                );
        }

        // Recherche spécifique sur le numéro SIRET.
        if (!empty($filters['companySiret'])) {
            $queryBuilder
                ->andWhere('d.companySiret LIKE :companySiret')
                ->setParameter(
                    'companySiret',
                    '%' . trim($filters['companySiret']) . '%'
                );
        }

        /**
         * Filtre sur la date minimale.
         *
         * Tous les dons postérieurs à cette date
         * seront inclus dans les résultats.
         */
        if (!empty($filters['dateStart'])) {
            $queryBuilder
                ->andWhere('d.donationDate >= :dateStart')
                ->setParameter(
                    'dateStart',
                    $filters['dateStart']
                );
        }

        /**
         * Filtre sur la date maximale.
         *
         * Tous les dons antérieurs à cette date
         * seront inclus dans les résultats.
         */
        if (!empty($filters['dateEnd'])) {
            $queryBuilder
                ->andWhere('d.donationDate <= :dateEnd')
                ->setParameter(
                    'dateEnd',
                    $filters['dateEnd']
                );
        }
    }

    /**
     * Calcule le montant total des dons validés.
     *
     * Les filtres de recherche sont conservés,
     * mais le statut est forcé sur "DONATION_VALIDEE"
     * afin de ne prendre en compte que les dons réellement encaissés.
     *
     * @param array<string, mixed> $filters
     *
     * @return float
     */
    public function getValidatedTotalAmount(array $filters): float
    {
        // On ignore volontairement le filtre de statut sélectionné.
        // Le compteur admin doit toujours représenter l'argent réellement validé.
        unset($filters['status']);

        $queryBuilder = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->andWhere('d.status = :validatedStatus')
            ->setParameter(
                'validatedStatus',
                DonationStatus::DONATION_VALIDEE
            );

        // Réutilisation des mêmes filtres que la liste des dons.
        $this->applyDonationFilters($queryBuilder, $filters);

        // Retourne 0 si aucun résultat n'est trouvé.
        return (float) (
            $queryBuilder->getQuery()->getSingleScalarResult() ?? 0
        );
    }
}
