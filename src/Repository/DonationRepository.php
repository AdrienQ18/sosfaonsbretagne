<?php

namespace App\Repository;

use App\Entity\Donation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Donation>
 */
class DonationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Donation::class);
    }

    public function searchDonations(array $filters): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('d')
            ->orderBy('d.donationDate', 'DESC');

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
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['donorType'])) {
            $queryBuilder
                ->andWhere('d.donorType = :donorType')
                ->setParameter('donorType', $filters['donorType']);
        }

        if (!empty($filters['status'])) {
            $queryBuilder
                ->andWhere('d.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['email'])) {
            $queryBuilder
                ->andWhere('d.email LIKE :email')
                ->setParameter('email', '%' . $filters['email'] . '%');
        }

        if (!empty($filters['companySiret'])) {
            $queryBuilder
                ->andWhere('d.companySiret LIKE :companySiret')
                ->setParameter('companySiret', '%' . $filters['companySiret'] . '%');
        }

        return $queryBuilder;
    }
}
