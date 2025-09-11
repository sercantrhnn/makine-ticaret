<?php

namespace App\Repository;

use App\Entity\BidOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BidOffer>
 */
class BidOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BidOffer::class);
    }

    public function countByBidId(int $bidId): int
    {
        return (int)$this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('IDENTITY(o.bid) = :bidId')
            ->setParameter('bidId', $bidId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}


