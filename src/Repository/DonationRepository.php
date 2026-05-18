<?php

namespace App\Repository;

use App\Entity\Donation;
use App\Enum\DonationStatus;
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
        $queryBuilder = $this->createQueryBuilder('d');

        $this->applyDonationFilters($queryBuilder, $filters);

        return $queryBuilder->orderBy('d.donationDate', 'DESC');

    }
    private function applyDonationFilters(QueryBuilder $queryBuilder, array $filters): void
    {
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

        if (isset($filters['donorType']) && $filters['donorType'] !== '') {
            $queryBuilder
                ->andWhere('d.donorType = :donorType')
                ->setParameter('donorType', $filters['donorType']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
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
        if (!empty($filters['dateStart'])) {
            $queryBuilder
                ->andWhere('d.donationDate >= :dateStart')
                ->setParameter('dateStart', $filters['dateStart']);
        }

        if (!empty($filters['dateEnd'])) {
            $queryBuilder
                ->andWhere('d.donationDate <= :dateEnd')
                ->setParameter('dateEnd', $filters['dateEnd']);
        }

    }
    public function getValidatedTotalAmount(array $filters): float
    {
        unset($filters['status']);

        $queryBuilder = $this->createQueryBuilder('d')
            ->select('SUM(d.amount)')
            ->andWhere('d.status = :validatedStatus')
            ->setParameter('validatedStatus', DonationStatus::DONATION_VALIDEE);

        $this->applyDonationFilters($queryBuilder, $filters);

        return (float) ($queryBuilder->getQuery()->getSingleScalarResult() ?? 0);
    }
}
