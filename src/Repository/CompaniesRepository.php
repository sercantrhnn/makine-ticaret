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
    public function findWithPaginationAndSearch(int $page, int $limit, string $search = '', ?string $sort = null): array
    {
        $qb = $this->createQueryBuilder('c');

        // Sorting
        switch ($sort) {
            case 'name_desc':
                $qb->orderBy('c.name', 'DESC');
                break;
            case 'name_asc':
            default:
                $qb->orderBy('c.name', 'ASC');
                break;
        }
        
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

    /**
     * Filtrelerle firmaları getir: belirli kategorilerde (veya altlarında) ürünü olan firmalar
     */
    public function findWithCategoryFilter(int $page, int $limit, string $search = '', ?array $categoryIds = null, ?string $sort = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.products', 'p')
            ->leftJoin('p.category', 'cat')
            ->addSelect('c')
            ->groupBy('c.id')
            ;

        switch ($sort) {
            case 'name_desc':
                $qb->orderBy('c.name', 'DESC');
                break;
            case 'name_asc':
            default:
                $qb->orderBy('c.name', 'ASC');
                break;
        }

        if (!empty($search)) {
            $qb->andWhere('LOWER(c.name) LIKE LOWER(:search) OR LOWER(c.about) LIKE LOWER(:search) OR LOWER(c.short_name) LIKE LOWER(:search) OR LOWER(c.title) LIKE LOWER(:search) OR LOWER(c.activityType) LIKE LOWER(:search) OR LOWER(c.phone) LIKE LOWER(:search) OR LOWER(c.email) LIKE LOWER(:search) OR LOWER(c.website) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($categoryIds && count($categoryIds) > 0) {
            $qb->andWhere('cat IN (:categoryIds)')->setParameter('categoryIds', $categoryIds);
        }

        return $qb->setFirstResult(($page - 1) * $limit)
                 ->setMaxResults($limit)
                 ->getQuery()
                 ->getResult();
    }

    /**
     * Filtrelerle toplam firma sayısını getir
     */
    public function countWithCategoryFilter(string $search = '', ?array $categoryIds = null): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.id)')
            ->leftJoin('c.products', 'p')
            ->leftJoin('p.category', 'cat');

        if (!empty($search)) {
            $qb->andWhere('LOWER(c.name) LIKE LOWER(:search) OR LOWER(c.about) LIKE LOWER(:search) OR LOWER(c.short_name) LIKE LOWER(:search) OR LOWER(c.title) LIKE LOWER(:search) OR LOWER(c.activityType) LIKE LOWER(:search) OR LOWER(c.phone) LIKE LOWER(:search) OR LOWER(c.email) LIKE LOWER(:search) OR LOWER(c.website) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($categoryIds && count($categoryIds) > 0) {
            $qb->andWhere('cat IN (:categoryIds)')->setParameter('categoryIds', $categoryIds);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }
}
