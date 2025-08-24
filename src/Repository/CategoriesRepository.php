<?php

namespace App\Repository;

use App\Entity\Categories;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Categories>
 *
 * @method Categories|null find($id, $lockMode = null, $lockVersion = null)
 * @method Categories|null findOneBy(array $criteria, array $orderBy = null)
 * @method Categories[]    findAll()
 * @method Categories[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Categories::class);
    }

    public function save(Categories $entity, bool $flush = false): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        if ($flush) { $em->flush(); }
    }

    public function remove(Categories $entity, bool $flush = false): void
    {
        $em = $this->getEntityManager();
        $em->remove($entity);
        if ($flush) { $em->flush(); }
    }

    /** Aktif kök kategoriler */
    public function findActiveRootCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Belirli bir kategorinin aktif çocukları */
    public function findActiveChildrenByParent(?Categories $parent): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent = :parent')
            ->andWhere('c.isActive = :active')
            ->setParameter('parent', $parent)
            ->setParameter('active', true)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Tüm ağaç (aktif) */
    public function findCategoryTree(): array
    {
        $roots = $this->findActiveRootCategories();
        $tree = [];
        foreach ($roots as $root) {
            $tree[] = $this->buildCategoryTree($root);
        }
        return $tree;
    }

    /** Ağaç düğümü oluşturma (recursive) */
    private function buildCategoryTree(Categories $category): array
    {
        $children = $this->findActiveChildrenByParent($category);
        $node = [
            'id'          => $category->getId(),
            'name'        => $category->getName(),
            'slug'        => $category->getSlug(),
            'description' => $category->getDescription(),
            'sortOrder'   => $category->getSortOrder(),
            'level'       => $category->getLevel(),
            'children'    => [],
        ];
        foreach ($children as $child) {
            $node['children'][] = $this->buildCategoryTree($child);
        }
        return $node;
    }

    /** Slug ile aktif kategori bul */
    public function findBySlug(string $slug): ?Categories
    {
        return $this->findOneBy(['slug' => $slug, 'isActive' => true]);
    }

    /**
     * Pagination ve search ile kategorileri getir
     */
    public function findWithPaginationAndSearch(int $page, int $limit, string $search = ''): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC');
        
        if (!empty($search)) {
            $qb->andWhere('LOWER(c.name) LIKE LOWER(:search) OR LOWER(c.description) LIKE LOWER(:search) OR LOWER(c.slug) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }
        
        return $qb->setFirstResult(($page - 1) * $limit)
                 ->setMaxResults($limit)
                 ->getQuery()
                 ->getResult();
    }
    
    /**
     * Search ile toplam kategori sayısını getir
     */
    public function countWithSearch(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)');
        
        if (!empty($search)) {
            $qb->andWhere('LOWER(c.name) LIKE LOWER(:search) OR LOWER(c.description) LIKE LOWER(:search) OR LOWER(c.slug) LIKE LOWER(:search)')
               ->setParameter('search', '%' . $search . '%');
        }
        
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * mpath kullanarak kategori + tüm alt kategorilerinin id'lerini getirir
     * (Materialized Path ile tek sorgu)
     */
    public function findDescendantIdsIncludingSelf(Categories $category): array
    {
        $mpath = (string)($category->getMpath() ?? '');

        // Root’lar genelde "/slug" gibi; altlarda "/slug/child/..."
        // LIKE 'mpath%' ile tüm altları (ve kendini) yakalayacağız.
        // NOT: Kendisi için OR c.id = :self koşulu da ekliyoruz.
        $qb = $this->createQueryBuilder('c')
            ->select('c.id')
            ->where('c.mpath LIKE :prefix')
            ->orWhere('c.id = :self')
            ->setParameter('prefix', $mpath === '' ? '%' : $mpath . '%')
            ->setParameter('self', $category->getId());

        return array_map('intval', array_column(
            $qb->getQuery()->getScalarResult(),
            'id'
        ));
    }

    /**
     * Kategori ve alt kategorilerindeki ürünleri getir
     * Not: Sayfalama/filtre ihtiyacın olursa parametreleştir.
     */
    public function findProductsInCategoryAndChildren(Categories $category): array
    {
        $ids = $this->findDescendantIdsIncludingSelf($category);

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('p')
            ->from('App\Entity\Products', 'p')
            ->where('p.category IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('p.id', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
