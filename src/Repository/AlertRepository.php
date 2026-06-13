<?php

namespace App\Repository;

use App\Entity\Alert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des signalements.
 *
 * Centralise les requêtes liées à l'entité Alert,
 * notamment la recherche filtrée utilisée dans l'administration.
 *
 * @extends ServiceEntityRepository<Alert>
 */
class AlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alert::class);
    }

    /**
     * Retourne tous les signalements.
     *
     * @return Alert[]
     */
    public function readAlert(): array
    {
        return $this->findAll();
    }

    /**
     * Construit une requête filtrée pour la liste des signalements.
     *
     * Les filtres possibles sont :
     * - recherche globale ;
     * - statut ;
     * - type ;
     * - localisation ;
     * - type de culture ;
     * - date de début ;
     * - date de fin.
     */
    public function findByFiltersQuery(array $filters): QueryBuilder
    {
        // Création de la requête de base avec jointure sur l'utilisateur.
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->addSelect('u')
            ->orderBy('a.creationDate', 'DESC');

        // Recherche globale sur plusieurs champs du signalement et sur l'email utilisateur.
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $qb
                ->andWhere('
                    a.type LIKE :search
                    OR a.localisation LIKE :search
                    OR a.cultureType LIKE :search
                    OR a.description LIKE :search
                    OR u.email LIKE :search
                ')
                ->setParameter('search', '%' . $search . '%');
        }

        // Filtre par statut du signalement.
        if (!empty($filters['status'])) {
            $qb
                ->andWhere('a.status = :status')
                ->setParameter('status', $filters['status']);
        }

        // Filtre par type de signalement.
        if (!empty($filters['type'])) {
            $qb
                ->andWhere('a.type LIKE :type')
                ->setParameter('type', '%' . trim($filters['type']) . '%');
        }

        // Filtre par localisation.
        if (!empty($filters['localisation'])) {
            $qb
                ->andWhere('a.localisation LIKE :localisation')
                ->setParameter(
                    'localisation',
                    '%' . trim($filters['localisation']) . '%'
                );
        }

        // Filtre par type de culture concernée.
        if (!empty($filters['cultureType'])) {
            $qb
                ->andWhere('a.cultureType LIKE :cultureType')
                ->setParameter(
                    'cultureType',
                    '%' . trim($filters['cultureType']) . '%'
                );
        }

        // Filtre sur la date de début avec heure forcée au début de journée.
        if (!empty($filters['dateStart'])) {
            $qb
                ->andWhere('a.creationDate >= :dateStart')
                ->setParameter(
                    'dateStart',
                    $filters['dateStart']->setTime(0, 0, 0)
                );
        }

        // Filtre sur la date de fin avec heure forcée à la fin de journée.
        if (!empty($filters['dateEnd'])) {
            $qb
                ->andWhere('a.creationDate <= :dateEnd')
                ->setParameter(
                    'dateEnd',
                    $filters['dateEnd']->setTime(23, 59, 59)
                );
        }

        return $qb;
    }
}
