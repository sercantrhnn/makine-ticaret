<?php

namespace App\Repository;

use App\Entity\Companies;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Companies>
 */
class CompaniesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Companies::class);
    }

    //    /**
    //     * @return Companies[] Returns an array of Companies objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * Pagination ve search ile firmaları getir
     */
    public function findWithPaginationAndSearch(int $page, int $limit, string $search = ''): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');
        
        if (!empty($search)) {
            $qb->andWhere('LOWER(c.name) LIKE LOWER(:search) OR LOWER(c.about) LIKE LOWER(:search) OR LOWER(c.short_name) LIKE LOWER(:search) OR LOWER(c.title) LIKE LOWER(:search) OR LOWER(c.activityType) LIKE LOWER(:search) OR LOWER(c.phone) LIKE LOWER(:search) OR LOWER(c.email) LIKE LOWER(:search) OR LOWER(c.website) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }
        
        return $qb->setFirstResult(($page - 1) * $limit)
                 ->setMaxResults($limit)
                 ->getQuery()
                 ->getResult();
    }
    
    /**
     * Search ile toplam firma sayısını getir
     */
    public function countWithSearch(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)');
        
        if (!empty($search)) {
            $qb->andWhere('LOWER(c.name) LIKE LOWER(:search) OR LOWER(c.about) LIKE LOWER(:search) OR LOWER(c.short_name) LIKE LOWER(:search) OR LOWER(c.title) LIKE LOWER(:search) OR LOWER(c.activityType) LIKE LOWER(:search) OR LOWER(c.phone) LIKE LOWER(:search) OR LOWER(c.email) LIKE LOWER(:search) OR LOWER(c.website) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }
        
        return $qb->getQuery()->getSingleScalarResult();
    }
}
