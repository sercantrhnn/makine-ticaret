<?php

namespace App\Controller;

use App\Entity\Bids;
use App\Entity\Companies;
use App\Entity\Products;
use App\Form\BidsType;
use App\Repository\BidsRepository;
use App\Repository\BidOfferRepository;
use App\Repository\CompaniesRepository;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/bids')]
#[IsGranted('ROLE_SUBSCRIBER')]
class BidsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BidsRepository $bidsRepository,
        private CompaniesRepository $companiesRepository,
        private ProductsRepository $productsRepository,
        private BidOfferRepository $bidOfferRepository
    ) {}

    #[Route('/', name: 'app_bids_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $bids = [];

        // Admin kullanıcıları tüm ihaleleri görebilir
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $bids = $this->bidsRepository->findAll();
        } else {
            // Normal kullanıcılar sadece kendi şirketlerindeki ihaleleri görebilir
            foreach ($user->getCompanies() as $company) {
                foreach ($company->getBids() as $bid) {
                    $bids[] = $bid;
                }
            }
        }

        // Teklif sayıları
        $offerCounts = [];
        foreach ($bids as $bid) {
            $offerCounts[$bid->getId()] = $this->bidOfferRepository->countByBidId($bid->getId());
        }

        return $this->render('admin/bids/index.html.twig', [
            'bids' => $bids,
            'isAdmin' => in_array('ROLE_ADMIN', $user->getRoles()),
            'offerCounts' => $offerCounts,
        ]);
    }

    #[Route('/new', name: 'app_bids_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $bid = new Bids();
        $form = $this->createForm(BidsType::class, $bid);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Kullanıcının ilk şirketini otomatik ata
            $user = $this->getUser();
            if ($user && $user->getCompanies()->count() > 0) {
                $bid->setCompany($user->getCompanies()->first());
            }
            
            // Status'u pending olarak ayarla
            $bid->setStatus('pending');
            
            $this->entityManager->persist($bid);
            $this->entityManager->flush();

            $this->addFlash('success', 'İhale başarıyla oluşturuldu.');

            return $this->redirectToRoute('app_bids_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/bids/new.html.twig', [
            'bid' => $bid,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bids_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Bids $bid): Response
    {
        $user = $this->getUser();
        
        // Admin kullanıcıları tüm ihaleleri görebilir
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            // Normal kullanıcılar sadece kendi şirketlerindeki ihaleleri görebilir
            if (!$this->isUserAuthorizedForBid($bid, $user)) {
                throw $this->createAccessDeniedException('Bu ihale için yetkiniz bulunmamaktadır.');
            }
        }

        $offers = $this->bidOfferRepository->findBy(['bid' => $bid], ['createdAt' => 'DESC']);

        return $this->render('admin/bids/show.html.twig', [
            'bid' => $bid,
            'isAdmin' => in_array('ROLE_ADMIN', $user->getRoles()),
            'offers' => $offers,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_bids_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Bids $bid): Response
    {
        $user = $this->getUser();
        
        // Admin kullanıcıları tüm ihaleleri düzenleyebilir
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            // Normal kullanıcılar sadece kendi şirketlerindeki ihaleleri düzenleyebilir
            if (!$this->isUserAuthorizedForBid($bid, $user)) {
                throw $this->createAccessDeniedException('Bu ihale için yetkiniz bulunmamaktadır.');
            }
            
            // Normal kullanıcılar sadece 'pending' durumundaki ihaleleri düzenleyebilir
            if ($bid->getStatus() !== 'pending') {
                $this->addFlash('error', 'Onaylanmış veya reddedilmiş ihaleler düzenlenemez.');
                return $this->redirectToRoute('app_bids_show', ['id' => $bid->getId()], Response::HTTP_SEE_OTHER);
            }
        }

        $form = $this->createForm(BidsType::class, $bid);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Şirket bilgisini koru (değiştirme)
            // $bid->setCompany() çağrılmıyor çünkü mevcut şirket korunmalı
            
            $this->entityManager->flush();

            $this->addFlash('success', 'İhale başarıyla güncellendi.');

            return $this->redirectToRoute('app_bids_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/bids/edit.html.twig', [
            'bid' => $bid,
            'form' => $form,
            'isAdmin' => in_array('ROLE_ADMIN', $user->getRoles()),
        ]);
    }

    #[Route('/{id}', name: 'app_bids_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Bids $bid): Response
    {
        $user = $this->getUser();
        
        // Admin kullanıcıları tüm ihaleleri silebilir
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            // Normal kullanıcılar sadece kendi şirketlerindeki ihaleleri silebilir
            if (!$this->isUserAuthorizedForBid($bid, $user)) {
                throw $this->createAccessDeniedException('Bu ihale için yetkiniz bulunmamaktadır.');
            }
        }

        if ($this->isCsrfTokenValid('delete'.$bid->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($bid);
            $this->entityManager->flush();

            $this->addFlash('success', 'İhale başarıyla silindi.');
        }

        return $this->redirectToRoute('app_bids_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/approve', name: 'app_bids_approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(Bids $bid): Response
    {
        $bid->setStatus('approved');
        $this->entityManager->flush();

        $this->addFlash('success', 'İhale başarıyla onaylandı.');

        return $this->redirectToRoute('app_bids_show', ['id' => $bid->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/reject', name: 'app_bids_reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(Bids $bid): Response
    {
        $bid->setStatus('rejected');
        $this->entityManager->flush();

        $this->addFlash('success', 'İhale reddedildi.');

        return $this->redirectToRoute('app_bids_show', ['id' => $bid->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/api/products-by-company/{companyId}', name: 'app_bids_products_by_company', methods: ['GET'], requirements: ['companyId' => '\d+'])]
    public function getProductsByCompany(int $companyId): JsonResponse
    {
        $user = $this->getUser();
        $company = $this->companiesRepository->find($companyId);

        // Kullanıcının bu şirkete erişimi var mı kontrol et
        if (!$company || !$user->getCompanies()->contains($company)) {
            return new JsonResponse(['error' => 'Yetkisiz erişim'], 403);
        }

        $products = $company->getProducts();
        $productData = [];

        foreach ($products as $product) {
            $productData[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'brand' => $product->getBrand(),
                'modelType' => $product->getModelType(),
                'displayName' => $product->getName() . ' (' . $product->getBrand() . ' - ' . $product->getModelType() . ')'
            ];
        }

        return new JsonResponse($productData);
    }

    /**
     * Kullanıcının belirli bir ihale üzerinde yetkisi olup olmadığını kontrol eder
     */
    private function isUserAuthorizedForBid(Bids $bid, $user): bool
    {
        if (!$user) {
            return false;
        }

        // Kullanıcının şirketlerinden birine ait mi kontrol et
        foreach ($user->getCompanies() as $company) {
            if ($bid->getCompany() === $company) {
                return true;
            }
        }

        return false;
    }
}
