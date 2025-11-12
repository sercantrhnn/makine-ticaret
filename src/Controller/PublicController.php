<?php

namespace App\Controller;

use App\Entity\Bids;
use App\Entity\Categories;
use App\Entity\Companies;
use App\Entity\Products;
use App\Repository\BidsRepository;
use App\Repository\CategoriesRepository;
use App\Repository\CompaniesRepository;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class PublicController extends AbstractController
{
//    #[Route('/', name: 'public_home')]
//    public function home(Request $request, CategoriesRepository $categoriesRepository, ProductsRepository $productsRepository, CompaniesRepository $companiesRepository, BidsRepository $bidsRepository): Response
//    {
//        // Ana sayfa için featured ürünler ve şirketler
//        $featuredProducts = $productsRepository->findBy([], ['createdAt' => 'DESC'], 8);
//        $featuredCompanies = $companiesRepository->findBy([], ['createdAt' => 'DESC'], 6);
//
//        // Ana kategorileri entity olarak al (ilk 8 ana kategori)
//        $categories = $categoriesRepository->findBy(['parent' => null, 'isActive' => true], ['sortOrder' => 'ASC', 'name' => 'ASC'], 8);
//
//        // Aktif ihaleler (onaylanan ve henüz bitmemiş)
//        $activeBids = $bidsRepository->findActiveBids(6);
//        $recentBids = $bidsRepository->findApprovedBids(8);
//
//        // Arama işlemi
//        $search = $request->query->get('search', '');
//        $searchResults = [];
//
//        if ($search) {
//            $searchResults = $productsRepository->searchProducts($search);
//        }
//
//        return $this->render('public/home.html.twig', [
//            'featuredProducts' => $featuredProducts,
//            'featuredCompanies' => $featuredCompanies,
//            'categories' => $categories,
//            'activeBids' => $activeBids,
//            'recentBids' => $recentBids,
//            'search' => $search,
//            'searchResults' => $searchResults,
//        ]);
//    }

    #[Route('/', name: 'public_home')]
    public function home2(Request $request, CategoriesRepository $categoriesRepository, BidsRepository $bidsRepository, \App\Repository\PurchaseRequestRepository $purchaseRequestRepository): Response
    {
        // Mevcut locale'i al
        $locale = $request->getLocale();

        // Kategorileri al (artık smart translation kullanıyoruz)
        $categories = $categoriesRepository->findActiveRootCategories();
        $categories = array_slice($categories, 0, 8); // İlk 8 kategori

        $approvedBids = $bidsRepository->findApprovedBids(8);
        $purchaseRequests = $purchaseRequestRepository->findBy(['status' => 'approved'], ['date' => 'DESC'], 10);

        return $this->render('public/home2.html.twig', [
            'categories' => $categories,
            'approvedBids' => $approvedBids,
            'purchaseRequests' => $purchaseRequests,
        ]);
    }

    #[Route('/products', name: 'public_products')]
    public function products(Request $request, ProductsRepository $productsRepository, CategoriesRepository $categoriesRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 12;
        $search = $request->query->get('search', '');
        $categoryId = $request->query->get('category');
        $sort = $request->query->get('sort');

        $category = null;
        if ($categoryId) {
            $category = $categoriesRepository->find($categoryId);
        }

        // Ürünleri getir (pagination ile)
        $products = $productsRepository->findWithFilters($page, $limit, $search, $category, $sort);
        $totalProducts = $productsRepository->countWithFilters($search, $category);
        $totalPages = ceil($totalProducts / $limit);

        $categories = $categoriesRepository->findBy(['isActive' => true], ['sortOrder' => 'ASC', 'name' => 'ASC']);

        return $this->render('public/products.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $category,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalProducts' => $totalProducts,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/product/{id}', name: 'public_product_detail', requirements: ['id' => '\d+'])]
    public function productDetail(Products $product): Response
    {
        return $this->render('public/product_detail.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/companies', name: 'public_companies')]
    public function companies(Request $request, CompaniesRepository $companiesRepository, \App\Repository\CategoriesRepository $categoriesRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 12;
        $search = $request->query->get('search', '');
        $categoryId = $request->query->getInt('category');
        $sort = $request->query->get('sort');
        $city = $request->query->get('city') ?: null;

        $categoryIds = null;
        $selectedCategory = null;
        if ($categoryId) {
            // Top-level ve alt kategoriler dahil
            // Basit yaklaşım: doğrudan ürünlerin category.id eşleşmesi; altları da kapsamak için CategoriesRepository gerekebilir
            // Burada sadece seçilen id ile filtreleyelim; alt kategoriler istenirse ileride genişletilir
            $categoryIds = [$categoryId];
        }

        if ($categoryIds) {
            $companies = $companiesRepository->findWithCategoryFilter($page, $limit, $search, $categoryIds, $sort, $city);
            $totalCompanies = $companiesRepository->countWithCategoryFilter($search, $categoryIds, $city);
        } else {
            $companies = $companiesRepository->findWithPaginationAndSearch($page, $limit, $search, $sort, $city);
            $totalCompanies = $companiesRepository->countWithSearch($search, $city);
        }
        $totalPages = ceil($totalCompanies / $limit);

        $categories = $categoriesRepository->findBy(['isActive' => true], ['sortOrder' => 'ASC', 'name' => 'ASC']);
        $cities = $companiesRepository->getDistinctCities();

        return $this->render('public/companies.html.twig', [
            'companies' => $companies,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCompanies' => $totalCompanies,
            'search' => $search,
            'selectedCategoryId' => $categoryId ?: null,
            'categories' => $categories,
            'sort' => $sort,
            'cities' => $cities,
            'selectedCity' => $city,
        ]);
    }

    #[Route('/company/{id}', name: 'public_company_detail', requirements: ['id' => '\d+'])]
    public function companyDetail(Companies $company, ProductsRepository $productsRepository): Response
    {
        $products = $productsRepository->findBy(['company' => $company], ['createdAt' => 'DESC']);
        $mapEmbedUrl = $this->buildMapEmbedUrl($company->getMapsUrl());

        return $this->render('public/company_detail.html.twig', [
            'company' => $company,
            'products' => $products,
            'mapEmbedUrl' => $mapEmbedUrl,
        ]);
    }

    #[Route('/categories', name: 'public_categories')]
    public function categories(CategoriesRepository $categoriesRepository): Response
    {
        // Ana kategorileri entity olarak al (children relationship'ler otomatik yüklenecek)
        $categories = $categoriesRepository->findBy(['parent' => null, 'isActive' => true], ['sortOrder' => 'ASC', 'name' => 'ASC']);

        return $this->render('public/categories.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/bids', name: 'public_bids')]
    public function bids(Request $request, BidsRepository $bidsRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 12;
        $search = $request->query->get('search', '');

        // Onaylanan ihaleleri getir (pagination ile)
        $bids = $bidsRepository->findApprovedWithFilters($page, $limit, $search);
        $totalBids = $bidsRepository->countApprovedWithFilters($search);
        $totalPages = ceil($totalBids / $limit);

        return $this->render('public/bids.html.twig', [
            'bids' => $bids,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalBids' => $totalBids,
            'search' => $search,
        ]);
    }

    #[Route('/bid/{id}', name: 'public_bid_detail', requirements: ['id' => '\d+'])]
    public function bidDetail(Bids $bid): Response
    {
        // Sadece onaylanan ihaleleri göster
        if ($bid->getStatus() !== 'approved') {
            throw $this->createNotFoundException('İhale bulunamadı.');
        }

        return $this->render('public/bid_detail.html.twig', [
            'bid' => $bid,
        ]);
    }

    /**
     * Bir kategorideki ve tüm alt kategorilerindeki ürünleri recursive olarak al
     */
    private function getAllProductsFromCategory(Categories $category, ProductsRepository $productsRepository): array
    {
        $products = [];

        // Mevcut kategorideki ürünleri ekle
        $directProducts = $productsRepository->findBy(['category' => $category]);
        $products = array_merge($products, $directProducts);

        // Alt kategorilerdeki ürünleri recursive olarak al
        foreach ($category->getChildren() as $child) {
            $childProducts = $this->getAllProductsFromCategory($child, $productsRepository);
            $products = array_merge($products, $childProducts);
        }

        return $products;
    }

    /**
     * Verilen maps URL'den güvenilir bir Google Maps embed URL üretir.
     * Desteklenen durumlar: embed URL, @lat,long, q paramı, /place/ yolu.
     */
    private function buildMapEmbedUrl(?string $mapsUrl): ?string
    {
        if (!$mapsUrl) {
            return null;
        }

        // Zaten embed ise doğrudan kullan
        if (strpos($mapsUrl, '/embed') !== false) {
            return $mapsUrl;
        }

        $queryParam = null;
        $llParam = null;

        // URL parse etmeyi dene
        $parsed = @parse_url($mapsUrl);
        if (is_array($parsed)) {
            // q parametresi
            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $qs);
                if (!empty($qs['q'])) {
                    $queryParam = trim((string) $qs['q']);
                }
            }

            // /place/<isim> yakala
            if (!$queryParam && !empty($parsed['path']) && strpos($parsed['path'], '/place/') !== false) {
                $after = substr($parsed['path'], strpos($parsed['path'], '/place/') + 7);
                $place = strtok($after, '/');
                if ($place) {
                    $queryParam = urldecode(str_replace('+', ' ', $place));
                }
            }
        }

        // @lat,long,zoom deseni
        if (!$queryParam && preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $mapsUrl, $m)) {
            $lat = $m[1];
            $lng = $m[2];
            $queryParam = $lat . ',' . $lng;
            $llParam = $queryParam;
        }

        // Hiçbir şey bulunamadıysa, tüm URL'yi arama paramı yap (en azından merkezlemeye çalışır)
        if (!$queryParam) {
            $queryParam = $mapsUrl;
        }

        $base = 'https://www.google.com/maps?output=embed&hl=tr&z=14&q=' . rawurlencode($queryParam);
        if ($llParam) {
            $base .= '&ll=' . rawurlencode($llParam);
        }
        return $base;
    }
}
