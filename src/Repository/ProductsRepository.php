<?php

namespace App\Repository;

use App\Entity\Products;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Products>
 */
class ProductsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Products::class);
    }

//    /**
//     * @return Products[] Returns an array of Products objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

    /**
     * Pagination ve search ile ürünleri getir
     */
    public function findWithPaginationAndSearch(int $page, int $limit, string $search = ''): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.company', 'c')
            ->leftJoin('p.category', 'cat')
            ->orderBy('p.id', 'DESC');
        
        if (!empty($search)) {
            $qb->andWhere('LOWER(p.name) LIKE LOWER(:search) OR LOWER(p.brand) LIKE LOWER(:search) OR LOWER(p.modelType) LIKE LOWER(:search) OR LOWER(p.type) LIKE LOWER(:search) OR LOWER(p.origin) LIKE LOWER(:search) OR LOWER(c.name) LIKE LOWER(:search) OR LOWER(cat.name) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }
        
        return $qb->setFirstResult(($page - 1) * $limit)
                 ->setMaxResults($limit)
                 ->getQuery()
                 ->getResult();
    }
    
    /**
     * Search ile toplam ürün sayısını getir
     */
    public function countWithSearch(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.company', 'c')
            ->leftJoin('p.category', 'cat')
            ->select('COUNT(p.id)');
        
        if (!empty($search)) {
            $qb->andWhere('LOWER(p.name) LIKE LOWER(:search) OR LOWER(p.brand) LIKE LOWER(:search) OR LOWER(p.modelType) LIKE LOWER(:search) OR LOWER(p.type) LIKE LOWER(:search) OR LOWER(p.origin) LIKE LOWER(:search) OR LOWER(c.name) LIKE LOWER(:search) OR LOWER(cat.name) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }
        
        return $qb->getQuery()->getSingleScalarResult();
    }
}
