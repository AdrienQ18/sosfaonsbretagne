<?php

namespace App\Repository;

use App\Entity\PreOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des précommandes.
 *
 * Centralise les requêtes liées aux précommandes,
 * notamment les recherches filtrées utilisées
 * dans l'interface d'administration.
 *
 * @extends ServiceEntityRepository<PreOrder>
 */
class PreOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PreOrder::class);
    }

    /**
     * Construit une requête filtrée pour la liste des précommandes.
     *
     * Les filtres disponibles sont :
     * - recherche globale ;
     * - statut ;
     * - email ;
     * - date de début ;
     * - date de fin.
     *
     * @param array<string, mixed> $filters
     *
     * @return QueryBuilder
     */
    public function findByFiltersQuery(array $filters): QueryBuilder
    {
        // Requête de base avec jointure sur l'utilisateur.
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.preOrderDate', 'DESC');

        /**
         * Recherche globale.
         *
         * Permet de rechercher :
         * - le prénom ;
         * - le nom ;
         * - l'email ;
         * - l'identifiant de la précommande.
         */
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $orX = $qb->expr()->orX(
                'u.firstname LIKE :search',
                'u.lastname LIKE :search',
                'u.email LIKE :search'
            );

            // Si la recherche est numérique,
            // on ajoute une recherche sur l'identifiant.
            if (ctype_digit($search)) {
                $orX->add('p.id = :searchId');

                $qb->setParameter(
                    'searchId',
                    (int) $search
                );
            }

            $qb
                ->andWhere($orX)
                ->setParameter(
                    'search',
                    '%' . $search . '%'
                );
        }

        // Filtre par statut de précommande.
        if (!empty($filters['status'])) {
            $qb
                ->andWhere('p.status = :status')
                ->setParameter(
                    'status',
                    $filters['status']
                );
        }

        // Filtre spécifique sur l'email utilisateur.
        if (!empty($filters['email'])) {
            $qb
                ->andWhere('u.email LIKE :email')
                ->setParameter(
                    'email',
                    '%' . trim($filters['email']) . '%'
                );
        }

        /**
         * Filtre sur la date minimale.
         *
         * L'heure est forcée à 00:00:00 afin
         * d'inclure toute la journée sélectionnée.
         */
        if (!empty($filters['dateStart'])) {
            $qb
                ->andWhere('p.preOrderDate >= :dateStart')
                ->setParameter(
                    'dateStart',
                    $filters['dateStart']->setTime(0, 0, 0)
                );
        }

        /**
         * Filtre sur la date maximale.
         *
         * L'heure est forcée à 23:59:59 afin
         * d'inclure toute la journée sélectionnée.
         */
        if (!empty($filters['dateEnd'])) {
            $qb
                ->andWhere('p.preOrderDate <= :dateEnd')
                ->setParameter(
                    'dateEnd',
                    $filters['dateEnd']->setTime(23, 59, 59)
                );
        }

        return $qb;
    }
}
