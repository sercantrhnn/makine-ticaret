<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use App\Form\SubscriptionType;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/subscriptions')]
#[IsGranted('ROLE_ADMIN', message: 'Bu sayfaya erişim için admin yetkisi gereklidir.')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubscriptionRepository $subscriptionRepository
    ) {}

    #[Route('/', name: 'subscriptions_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = (int) $request->query->get('page', 1);
        $limit = 10;
        $search = $request->query->get('search', '');

        $subscriptions = $this->subscriptionRepository->findWithPaginationAndSearch($page, $limit, $search);
        $totalSubscriptions = $this->subscriptionRepository->countWithSearch($search);
        $totalPages = ceil($totalSubscriptions / $limit);

        return $this->render('admin/subscriptions/index.html.twig', [
            'subscriptions' => $subscriptions,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalSubscriptions' => $totalSubscriptions,
            'limit' => $limit,
            'search' => $search
        ]);
    }

    #[Route('/new', name: 'subscriptions_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        // Aboneliği olmayan kullanıcı sayısını kontrol et
        $usersWithoutSubscription = $this->subscriptionRepository->findUsersWithoutSubscription();
        
        if (empty($usersWithoutSubscription)) {
            $this->addFlash('warning', 'Tüm kullanıcıların zaten aboneliği bulunmaktadır. Yeni abonelik eklemek için önce mevcut abonelikleri düzenleyin.');
            return $this->redirectToRoute('subscriptions_index');
        }

        $subscription = new Subscription();
        $form = $this->createForm(SubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Seçilen kullanıcının zaten aboneliği var mı kontrol et (ek güvenlik)
            if ($this->subscriptionRepository->hasAnySubscription($subscription->getUser())) {
                $this->addFlash('error', 'Bu kullanıcının zaten bir aboneliği bulunmaktadır. Lütfen mevcut aboneliği düzenleyin.');
                return $this->render('admin/subscriptions/new.html.twig', [
                    'subscription' => $subscription,
                    'form' => $form,
                ]);
            }

            // EndDate'i tipine göre otomatik hesapla
            $this->calculateEndDate($subscription);
            
            // Kullanıcıya ROLE_SUBSCRIBER rolü ekle
            $this->addSubscriberRole($subscription->getUser());
            
            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonelik başarıyla oluşturuldu ve kullanıcıya abone rolü verildi.');

            return $this->redirectToRoute('subscriptions_show', ['id' => $subscription->getId()]);
        }

        return $this->render('admin/subscriptions/new.html.twig', [
            'subscription' => $subscription,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'subscriptions_show', methods: ['GET'])]
    public function show(Subscription $subscription): Response
    {
        return $this->render('admin/subscriptions/show.html.twig', [
            'subscription' => $subscription,
        ]);
    }

    #[Route('/{id}/edit', name: 'subscriptions_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Subscription $subscription): Response
    {
        $form = $this->createForm(SubscriptionType::class, $subscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // EndDate'i tipine göre otomatik hesapla
            $this->calculateEndDate($subscription);
            
            // Kullanıcıya ROLE_SUBSCRIBER rolü ekle
            $this->addSubscriberRole($subscription->getUser());
            
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonelik başarıyla güncellendi.');

            return $this->redirectToRoute('subscriptions_show', ['id' => $subscription->getId()]);
        }

        return $this->render('admin/subscriptions/edit.html.twig', [
            'subscription' => $subscription,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'subscriptions_delete', methods: ['POST'])]
    public function delete(Request $request, Subscription $subscription): Response
    {
        if ($this->isCsrfTokenValid('delete'.$subscription->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($subscription);
            $this->entityManager->flush();

            $this->addFlash('success', 'Abonelik başarıyla silindi.');
        }

        return $this->redirectToRoute('subscriptions_index');
    }

    #[Route('/stats', name: 'subscriptions_stats', methods: ['GET'])]
    public function stats(): Response
    {
        $stats = $this->subscriptionRepository->getSubscriptionStats();
        $expiringSoon = $this->subscriptionRepository->findSubscriptionsExpiringSoon(7);
        $expired = $this->subscriptionRepository->findExpiredSubscriptions();

        return $this->render('admin/subscriptions/stats.html.twig', [
            'stats' => $stats,
            'expiringSoon' => $expiringSoon,
            'expired' => $expired
        ]);
    }

    #[Route('/{id}/activate', name: 'subscriptions_activate', methods: ['POST'])]
    public function activate(Subscription $subscription): Response
    {
        $subscription->setStatus('active');
        
        // Kullanıcıya ROLE_SUBSCRIBER rolü ekle
        $this->addSubscriberRole($subscription->getUser());
        
        $this->entityManager->flush();

        $this->addFlash('success', 'Abonelik aktif edildi ve kullanıcıya abone rolü verildi.');

        return $this->redirectToRoute('subscriptions_show', ['id' => $subscription->getId()]);
    }

    #[Route('/{id}/cancel', name: 'subscriptions_cancel', methods: ['POST'])]
    public function cancel(Subscription $subscription): Response
    {
        $subscription->setStatus('cancelled');
        
        // Kullanıcıdan ROLE_SUBSCRIBER rolünü kaldır
        $this->removeSubscriberRole($subscription->getUser());
        
        $this->entityManager->flush();

        $this->addFlash('success', 'Abonelik iptal edildi ve kullanıcıdan abone rolü kaldırıldı.');

        return $this->redirectToRoute('subscriptions_show', ['id' => $subscription->getId()]);
    }

    /**
     * Abonelik tipine göre bitiş tarihini hesaplar
     */
    private function calculateEndDate(Subscription $subscription): void
    {
        if (!$subscription->getStartDate()) {
            $subscription->setStartDate(new \DateTime());
        }

        $startDate = $subscription->getStartDate();
        $endDate = clone $startDate;

        switch ($subscription->getType()) {
            case 'monthly':
                $endDate->modify('+1 month');
                break;
            case 'yearly':
                $endDate->modify('+1 year');
                break;
            case 'lifetime':
                // Yaşam boyu için 100 yıl ekle
                $endDate->modify('+100 years');
                break;
            default:
                // Varsayılan olarak 1 ay
                $endDate->modify('+1 month');
                break;
        }

        $subscription->setEndDate($endDate);
    }

    /**
     * Kullanıcıya ROLE_SUBSCRIBER rolü ekler
     */
    private function addSubscriberRole(User $user): void
    {
        $roles = $user->getRoles();
        
        if (!in_array('ROLE_SUBSCRIBER', $roles)) {
            $roles[] = 'ROLE_SUBSCRIBER';
            $user->setRoles($roles);
        }
    }

    /**
     * Kullanıcıdan ROLE_SUBSCRIBER rolünü kaldırır
     */
    private function removeSubscriberRole(User $user): void
    {
        $roles = $user->getRoles();
        $roles = array_filter($roles, function($role) {
            return $role !== 'ROLE_SUBSCRIBER';
        });
        
        $user->setRoles(array_values($roles));
    }
}