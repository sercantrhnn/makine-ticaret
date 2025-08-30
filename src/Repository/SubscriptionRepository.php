<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Kullanıcının aktif aboneliğini getirir
     */
    public function findActiveSubscriptionByUser(User $user): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.status = :status')
            ->andWhere('s.endDate > :now')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTime())
            ->orderBy('s.endDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Kullanıcının tüm aboneliklerini getirir
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Kullanıcının herhangi bir aboneliği var mı kontrol eder
     */
    public function hasAnySubscription(User $user): bool
    {
        $count = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Aboneliği olmayan kullanıcıları getirir
     */
    public function findUsersWithoutSubscription(): array
    {
        $em = $this->getEntityManager();
        
        // Aboneliği olan kullanıcıların ID'lerini al
        $subscribedUserIds = $this->createQueryBuilder('s')
            ->select('DISTINCT IDENTITY(s.user)')
            ->getQuery()
            ->getResult();

        $userIds = array_column($subscribedUserIds, 1); // IDENTITY sonucu array içinde gelir

        // Aboneliği olmayan kullanıcıları getir
        $qb = $em->getRepository(User::class)->createQueryBuilder('u');
        
        if (!empty($userIds)) {
            $qb->where('u.id NOT IN (:userIds)')
               ->setParameter('userIds', $userIds);
        }

        return $qb->orderBy('u.name', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Süresi dolmuş abonelikleri getirir
     */
    public function findExpiredSubscriptions(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.endDate <= :now')
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Yakında süresi dolacak abonelikleri getirir (7 gün içinde)
     */
    public function findSubscriptionsExpiringSoon(int $days = 7): array
    {
        $expiryDate = new \DateTime();
        $expiryDate->modify("+{$days} days");

        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.endDate <= :expiryDate')
            ->andWhere('s.endDate > :now')
            ->setParameter('status', 'active')
            ->setParameter('expiryDate', $expiryDate)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Belirli bir tipte aktif abonelikleri getirir
     */
    public function findActiveSubscriptionsByType(string $type): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.type = :type')
            ->andWhere('s.status = :status')
            ->andWhere('s.endDate > :now')
            ->setParameter('type', $type)
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Abonelik istatistiklerini getirir
     */
    public function getSubscriptionStats(): array
    {
        $qb = $this->createQueryBuilder('s');
        
        $total = $qb->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $active = $qb->select('COUNT(s.id)')
            ->where('s.status = :status')
            ->andWhere('s.endDate > :now')
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        $expired = $qb->select('COUNT(s.id)')
            ->where('s.status = :status')
            ->andWhere('s.endDate <= :now')
            ->setParameter('status', 'active')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired
        ];
    }

    /**
     * Sayfalama ile abonelikleri getirir
     */
    public function findWithPaginationAndSearch(int $page, int $limit, string $search = ''): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->orderBy('s.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if (!empty($search)) {
            $qb->where('u.name LIKE :search OR u.surname LIKE :search OR u.email LIKE :search OR s.type LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Arama kriterlerine göre abonelik sayısını getirir
     */
    public function countWithSearch(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->leftJoin('s.user', 'u');

        if (!empty($search)) {
            $qb->where('u.name LIKE :search OR u.surname LIKE :search OR u.email LIKE :search OR s.type LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}