<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository des utilisateurs.
 *
 * Centralise les requêtes utilisées par l'administration,
 * notamment la recherche filtrée de la liste utilisateurs.
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }


    public function findByFiltersQuery(array $filters): QueryBuilder
    {
        // La jointure évite des requêtes supplémentaires lors de l'affichage
        // du rôle métier de chaque utilisateur.
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.userRole', 'r')
            ->addSelect('r')
            ->orderBy('u.creationDate', 'DESC');

        // Recherche globale sur les champs les plus utilisés par l'admin.
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $qb
                ->andWhere('
                u.firstname LIKE :search
                OR u.lastname LIKE :search
                OR u.email LIKE :search
                OR u.phone LIKE :search
                OR u.city LIKE :search
                OR u.zipcode LIKE :search
            ')
                ->setParameter('search', '%' . $search . '%');
        }

        // Le filtre actif accepte false : on ne peut donc pas utiliser empty().
        if (array_key_exists('actif', $filters) && $filters['actif'] !== null && $filters['actif'] !== '') {
            $qb
                ->andWhere('u.actif = :actif')
                ->setParameter('actif', $filters['actif']);
        }

        // Filtre sur le rôle métier lié à l'entité Role.
        if (!empty($filters['role'])) {
            $qb
                ->andWhere('u.userRole = :role')
                ->setParameter('role', $filters['role']);
        }

        // Filtre sur les rôles Symfony stockés en JSON dans la colonne roles.
        if (!empty($filters['right'])) {
            $qb
                ->andWhere('u.roles LIKE :right')
                ->setParameter('right', '%' . $filters['right'] . '%');
        }

        // Début de période inclusif.
        if (!empty($filters['dateStart'])) {
            $qb
                ->andWhere('u.creationDate >= :dateStart')
                ->setParameter('dateStart', $filters['dateStart']->setTime(0, 0, 0));
        }

        // Fin de période inclusive jusqu'à la dernière seconde du jour.
        if (!empty($filters['dateEnd'])) {
            $qb
                ->andWhere('u.creationDate <= :dateEnd')
                ->setParameter('dateEnd', $filters['dateEnd']->setTime(23, 59, 59));
        }

        return $qb;
    }
}
