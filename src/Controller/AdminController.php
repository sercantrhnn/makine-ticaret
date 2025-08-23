<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Entity\Companies;
use App\Entity\Products;
use App\Repository\CategoriesRepository;
use App\Repository\CompaniesRepository;
use App\Repository\ProductsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(
        CompaniesRepository $companiesRepository,
        CategoriesRepository $categoriesRepository,
        ProductsRepository $productsRepository
    ): Response {
        $stats = [
            'companies' => $companiesRepository->count([]),
            'categories' => $categoriesRepository->count([]),
            'products' => $productsRepository->count([]),
        ];

        $recentProducts = $productsRepository->findBy([], ['id' => 'DESC'], 5);
        $recentCompanies = $companiesRepository->findBy([], ['id' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentProducts' => $recentProducts,
            'recentCompanies' => $recentCompanies,
        ]);
    }
}
