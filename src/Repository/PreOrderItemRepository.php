<?php

namespace App\Repository;

use App\Entity\PreOrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des lignes de précommande.
 *
 * Les lignes sont principalement manipulées via leur précommande parente.
 *
 * @extends ServiceEntityRepository<PreOrderItem>
 */
class PreOrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PreOrderItem::class);
    }

}
