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
            $qb->andWhere('LOWER(p.name) LIKE LOWER(:search) OR LOWER(p.description) LIKE LOWER(:search) OR LOWER(p.brand) LIKE LOWER(:search) OR LOWER(p.modelType) LIKE LOWER(:search) OR LOWER(c.name) LIKE LOWER(:search)')
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
            ->select('COUNT(p.id)');
        
        if (!empty($search)) {
            $qb->andWhere('LOWER(p.name) LIKE LOWER(:search) OR LOWER(p.description) LIKE LOWER(:search) OR LOWER(p.brand) LIKE LOWER(:search) OR LOWER(p.modelType) LIKE LOWER(:search) OR LOWER(c.name) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }
        
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Filtrelerle ürünleri getir
     */
    public function findWithFilters(int $page, int $limit, string $search = '', $category = null, ?string $sort = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.company', 'c')
            ->leftJoin('p.category', 'cat')
            ;

        // Sorting
        switch ($sort) {
            case 'price_asc':
                $qb->addSelect('(CASE WHEN p.price IS NULL THEN 1 ELSE 0 END) AS HIDDEN price_nulls')
                   ->orderBy('price_nulls', 'ASC')
                   ->addOrderBy('p.price', 'ASC');
                break;
            case 'price_desc':
                $qb->addSelect('(CASE WHEN p.price IS NULL THEN 1 ELSE 0 END) AS HIDDEN price_nulls')
                   ->orderBy('price_nulls', 'ASC')
                   ->addOrderBy('p.price', 'DESC');
                break;
            case 'name_asc':
                $qb->orderBy('p.name', 'ASC');
                break;
            case 'name_desc':
                $qb->orderBy('p.name', 'DESC');
                break;
            default:
                $qb->orderBy('p.createdAt', 'DESC');
        }
 
        if (!empty($search)) {
            $qb->andWhere('LOWER(p.name) LIKE LOWER(:search) OR LOWER(p.description) LIKE LOWER(:search) OR LOWER(p.brand) LIKE LOWER(:search) OR LOWER(p.modelType) LIKE LOWER(:search) OR LOWER(c.name) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }
 
        if ($category) {
            // Seçilen kategori ve tüm alt kategorilerini dahil et
            $categoryIds = $this->getAllCategoryIds($category);
            $qb->andWhere('p.category IN (:categoryIds)')
               ->setParameter('categoryIds', $categoryIds);
        }
 
        return $qb->setFirstResult(($page - 1) * $limit)
                 ->setMaxResults($limit)
                 ->getQuery()
                 ->getResult();
    }
    
    /**
     * Filtrelerle toplam ürün sayısını getir
     */
    public function countWithFilters(string $search = '', $category = null): int
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.company', 'c')
            ->select('COUNT(p.id)');
        
        if (!empty($search)) {
            $qb->andWhere('LOWER(p.name) LIKE LOWER(:search) OR LOWER(p.description) LIKE LOWER(:search) OR LOWER(p.brand) LIKE LOWER(:search) OR LOWER(p.modelType) LIKE LOWER(:search) OR LOWER(c.name) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }
        
        if ($category) {
            // Seçilen kategori ve tüm alt kategorilerini dahil et
            $categoryIds = $this->getAllCategoryIds($category);
            $qb->andWhere('p.category IN (:categoryIds)')
               ->setParameter('categoryIds', $categoryIds);
        }
        
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Ürün arama
     */
    public function searchProducts(string $search): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.company', 'c')
            ->leftJoin('p.category', 'cat')
            ->andWhere('LOWER(p.name) LIKE LOWER(:search) OR LOWER(p.description) LIKE LOWER(:search) OR LOWER(p.brand) LIKE LOWER(:search) OR LOWER(p.modelType) LIKE LOWER(:search) OR LOWER(c.name) LIKE LOWER(:search)')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    /**
     * Bir kategorinin kendisi ve tüm alt kategorilerinin ID'lerini getir (recursive)
     */
    private function getAllCategoryIds($category): array
    {
        $categoryIds = [$category->getId()];
        
        // Alt kategorileri recursive olarak ekle
        foreach ($category->getChildren() as $child) {
            if ($child->isActive()) {
                $categoryIds = array_merge($categoryIds, $this->getAllCategoryIds($child));
            }
        }
        
        return $categoryIds;
    }
}
