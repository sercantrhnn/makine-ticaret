<?php

namespace App\Repository;

use App\Entity\Bids;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bids>
 */
class BidsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bids::class);
    }

    /**
     * Onaylanan ihaleleri getir (en yeni önce)
     *
     * @return Bids[] Returns an array of approved Bids objects
     */
    public function findApprovedBids(int $limit = 10): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.company', 'c')
            ->leftJoin('b.product', 'p')
            ->addSelect('c', 'p')
            ->andWhere('b.status = :status')
            ->setParameter('status', 'approved')
            ->orderBy('b.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Aktif ihaleleri getir (onaylanan + henüz bitmemiş)
     *
     * @return Bids[] Returns an array of active Bids objects
     */
    public function findActiveBids(int $limit = 10): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.company', 'c')
            ->leftJoin('b.product', 'p')
            ->addSelect('c', 'p')
            ->andWhere('b.status = :status')
            ->andWhere('b.end_time > :now')
            ->setParameter('status', 'approved')
            ->setParameter('now', new \DateTime())
            ->orderBy('b.end_time', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Onaylanan ihaleler için pagination ve search
     */
    public function findApprovedWithFilters(int $page = 1, int $limit = 12, string $search = ''): array
    {
        $offset = ($page - 1) * $limit;
        
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.company', 'c')
            ->leftJoin('b.product', 'p')
            ->leftJoin('p.category', 'cat')
            ->addSelect('c', 'p', 'cat')
            ->andWhere('b.status = :status')
            ->setParameter('status', 'approved');

        if (!empty($search)) {
            $qb->andWhere('
                b.description LIKE :search OR 
                p.name LIKE :search OR 
                p.brand LIKE :search OR 
                p.modelType LIKE :search OR 
                c.name LIKE :search
            ')
            ->setParameter('search', '%' . $search . '%');
        }

        return $qb->orderBy('b.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Onaylanan ihaleler sayısını getir
     */
    public function countApprovedWithFilters(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->leftJoin('b.product', 'p')
            ->leftJoin('b.company', 'c')
            ->andWhere('b.status = :status')
            ->setParameter('status', 'approved');

        if (!empty($search)) {
            $qb->andWhere('
                b.description LIKE :search OR 
                p.name LIKE :search OR 
                p.brand LIKE :search OR 
                p.modelType LIKE :search OR 
                c.name LIKE :search
            ')
            ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
