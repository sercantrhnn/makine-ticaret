<?php

namespace App\Controller;

use App\Entity\Bids;
use App\Entity\Companies;
use App\Entity\Products;
use App\Form\BidsType;
use App\Repository\BidsRepository;
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
        private ProductsRepository $productsRepository
    ) {}

    #[Route('/', name: 'app_bids_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $bids = [];

        // Kullanıcının şirketlerindeki tüm ihaleleri getir
        foreach ($user->getCompanies() as $company) {
            foreach ($company->getBids() as $bid) {
                $bids[] = $bid;
            }
        }

        return $this->render('admin/bids/index.html.twig', [
            'bids' => $bids,
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
        // Kullanıcının bu ihale üzerinde yetkisi var mı kontrol et
        $user = $this->getUser();
        if (!$this->isUserAuthorizedForBid($bid, $user)) {
            throw $this->createAccessDeniedException('Bu ihale için yetkiniz bulunmamaktadır.');
        }

        return $this->render('admin/bids/show.html.twig', [
            'bid' => $bid,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_bids_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Bids $bid): Response
    {
        // Kullanıcının bu ihale üzerinde yetkisi var mı kontrol et
        $user = $this->getUser();
        if (!$this->isUserAuthorizedForBid($bid, $user)) {
            throw $this->createAccessDeniedException('Bu ihale için yetkiniz bulunmamaktadır.');
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
        ]);
    }

    #[Route('/{id}', name: 'app_bids_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Bids $bid): Response
    {
        // Kullanıcının bu ihale üzerinde yetkisi var mı kontrol et
        $user = $this->getUser();
        if (!$this->isUserAuthorizedForBid($bid, $user)) {
            throw $this->createAccessDeniedException('Bu ihale için yetkiniz bulunmamaktadır.');
        }

        if ($this->isCsrfTokenValid('delete'.$bid->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($bid);
            $this->entityManager->flush();

            $this->addFlash('success', 'İhale başarıyla silindi.');
        }

        return $this->redirectToRoute('app_bids_index', [], Response::HTTP_SEE_OTHER);
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
