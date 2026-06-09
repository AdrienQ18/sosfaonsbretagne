<?php

namespace App\Repository;

use App\Entity\PreOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PreOrder>
 */
class PreOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PreOrder::class);
    }

    public function findByFiltersQuery(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.preOrderDate', 'DESC');

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $orX = $qb->expr()->orX(
                'u.firstname LIKE :search',
                'u.lastname LIKE :search',
                'u.email LIKE :search'
            );

            if (ctype_digit($search)) {
                $orX->add('p.id = :searchId');
                $qb->setParameter('searchId', (int) $search);
            }

            $qb
                ->andWhere($orX)
                ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($filters['status'])) {
            $qb
                ->andWhere('p.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['email'])) {
            $qb
                ->andWhere('u.email LIKE :email')
                ->setParameter('email', '%' . trim($filters['email']) . '%');
        }

        if (!empty($filters['dateStart'])) {
            $qb
                ->andWhere('p.preOrderDate >= :dateStart')
                ->setParameter(
                    'dateStart',
                    $filters['dateStart']->setTime(0, 0, 0)
                );
        }

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
