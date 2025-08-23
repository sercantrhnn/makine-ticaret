<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Entity\Products;
use App\Repository\CategoriesRepository;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicController extends AbstractController
{
    #[Route('/', name: 'public_home')]
    public function home(Request $request, CategoriesRepository $categoriesRepository, ProductsRepository $productsRepository): Response
    {
        // Tüm kategorileri al (hiyerarşik sıralama için)
        $categories = $categoriesRepository->findBy([], ['sortOrder' => 'ASC', 'name' => 'ASC']);
        
        // Seçilen kategori ID'sini al
        $selectedCategoryId = $request->query->get('category');
        $selectedCategory = null;
        $products = [];
        
        if ($selectedCategoryId) {
            $selectedCategory = $categoriesRepository->find($selectedCategoryId);
            
            if ($selectedCategory) {
                // Seçilen kategorideki ürünleri al
                $directProducts = $productsRepository->findBy(['category' => $selectedCategory]);
                
                // Alt kategorilerdeki tüm ürünleri de al
                $allProducts = $this->getAllProductsFromCategory($selectedCategory, $productsRepository);
                
                $products = $allProducts;
            }
        }
        
        return $this->render('public/home.html.twig', [
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'products' => $products,
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
