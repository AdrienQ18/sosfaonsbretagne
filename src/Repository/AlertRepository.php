<?php

namespace App\Repository;

use App\Entity\Alert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Alert>
 */
class AlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alert::class);
    }

    public function readAlert()
    {
       return $this->findAll();
    }

    public function findByFiltersQuery(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->addSelect('u')
            ->orderBy('a.creationDate', 'DESC');

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

        if (!empty($filters['status'])) {
            $qb
                ->andWhere('a.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $qb
                ->andWhere('a.type LIKE :type')
                ->setParameter('type', '%' . trim($filters['type']) . '%');
        }

        if (!empty($filters['localisation'])) {
            $qb
                ->andWhere('a.localisation LIKE :localisation')
                ->setParameter('localisation', '%' . trim($filters['localisation']) . '%');
        }

        if (!empty($filters['cultureType'])) {
            $qb
                ->andWhere('a.cultureType LIKE :cultureType')
                ->setParameter('cultureType', '%' . trim($filters['cultureType']) . '%');
        }

        if (!empty($filters['dateStart'])) {
            $qb
                ->andWhere('a.creationDate >= :dateStart')
                ->setParameter('dateStart', $filters['dateStart']->setTime(0, 0, 0));
        }

        if (!empty($filters['dateEnd'])) {
            $qb
                ->andWhere('a.creationDate <= :dateEnd')
                ->setParameter('dateEnd', $filters['dateEnd']->setTime(23, 59, 59));
        }

        return $qb;
    }
}
