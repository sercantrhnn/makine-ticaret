<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Entity\Companies;
use App\Entity\Products;
use App\Repository\CategoriesRepository;
use App\Repository\CompaniesRepository;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicController extends AbstractController
{
    #[Route('/', name: 'public_home')]
    public function home(Request $request, CategoriesRepository $categoriesRepository, ProductsRepository $productsRepository, CompaniesRepository $companiesRepository): Response
    {
        // Ana sayfa için featured ürünler ve şirketler
        $featuredProducts = $productsRepository->findBy([], ['createdAt' => 'DESC'], 8);
        $featuredCompanies = $companiesRepository->findBy([], ['createdAt' => 'DESC'], 6);
        $categories = $categoriesRepository->findBy(['parent' => null, 'isActive' => true], ['sortOrder' => 'ASC', 'name' => 'ASC'], 8);
        
        // Arama işlemi
        $search = $request->query->get('search', '');
        $searchResults = [];
        
        if ($search) {
            $searchResults = $productsRepository->searchProducts($search);
        }
        
        return $this->render('public/home.html.twig', [
            'featuredProducts' => $featuredProducts,
            'featuredCompanies' => $featuredCompanies,
            'categories' => $categories,
            'search' => $search,
            'searchResults' => $searchResults,
        ]);
    }

    #[Route('/products', name: 'public_products')]
    public function products(Request $request, ProductsRepository $productsRepository, CategoriesRepository $categoriesRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 12;
        $search = $request->query->get('search', '');
        $categoryId = $request->query->get('category');
        
        $category = null;
        if ($categoryId) {
            $category = $categoriesRepository->find($categoryId);
        }
        
        // Ürünleri getir (pagination ile)
        $products = $productsRepository->findWithFilters($page, $limit, $search, $category);
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
    public function companies(Request $request, CompaniesRepository $companiesRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 12;
        $search = $request->query->get('search', '');
        
        $companies = $companiesRepository->findWithPaginationAndSearch($page, $limit, $search);
        $totalCompanies = $companiesRepository->countWithSearch($search);
        $totalPages = ceil($totalCompanies / $limit);
        
        return $this->render('public/companies.html.twig', [
            'companies' => $companies,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCompanies' => $totalCompanies,
            'search' => $search,
        ]);
    }

    #[Route('/company/{id}', name: 'public_company_detail', requirements: ['id' => '\d+'])]
    public function companyDetail(Companies $company, ProductsRepository $productsRepository): Response
    {
        $products = $productsRepository->findBy(['company' => $company], ['createdAt' => 'DESC']);
        
        return $this->render('public/company_detail.html.twig', [
            'company' => $company,
            'products' => $products,
        ]);
    }

    #[Route('/categories', name: 'public_categories')]
    public function categories(CategoriesRepository $categoriesRepository): Response
    {
        $categories = $categoriesRepository->findBy(['parent' => null, 'isActive' => true], ['sortOrder' => 'ASC', 'name' => 'ASC']);
        
        return $this->render('public/categories.html.twig', [
            'categories' => $categories,
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
}
