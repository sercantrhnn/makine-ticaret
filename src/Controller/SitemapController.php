<?php

namespace App\Controller;

use App\Repository\ProductsRepository;
use App\Repository\CompaniesRepository;
use App\Repository\CategoriesRepository;
use App\Repository\BidsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function sitemap(
        ProductsRepository $productsRepo,
        CompaniesRepository $companiesRepo,
        CategoriesRepository $categoriesRepo,
        BidsRepository $bidsRepo
    ): Response {
        // Static pages
        $urls = [
            [
                'loc' => $this->generateUrl('public_home'),
                'changefreq' => 'daily',
                'priority' => '1.0',
                'lastmod' => (new \DateTime())->format('Y-m-d')
            ],
            [
                'loc' => $this->generateUrl('public_products'),
                'changefreq' => 'daily', 
                'priority' => '0.9',
                'lastmod' => (new \DateTime())->format('Y-m-d')
            ],
            [
                'loc' => $this->generateUrl('public_companies'),
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'lastmod' => (new \DateTime())->format('Y-m-d')
            ],
            [
                'loc' => $this->generateUrl('public_categories'),
                'changefreq' => 'weekly',
                'priority' => '0.8',
                'lastmod' => (new \DateTime())->format('Y-m-d')
            ],
            [
                'loc' => $this->generateUrl('public_bids'),
                'changefreq' => 'daily',
                'priority' => '0.7',
                'lastmod' => (new \DateTime())->format('Y-m-d')
            ]
        ];

        // Products (SoftDeleteableEntity kullanıldığı için silinenler otomatik filtrelenir)
        $products = $productsRepo->findBy([], ['id' => 'DESC'], 1000);
        foreach ($products as $product) {
            $urls[] = [
                'loc' => $this->generateUrl('public_product_detail', ['id' => $product->getId()]),
                'changefreq' => 'weekly',
                'priority' => '0.6',
                'lastmod' => $product->getUpdatedAt() ? $product->getUpdatedAt()->format('Y-m-d') : (new \DateTime())->format('Y-m-d')
            ];
        }

        // Companies (SoftDeleteableEntity kullanıldığı için silinenler otomatik filtrelenir)
        $companies = $companiesRepo->findBy([], ['id' => 'DESC'], 1000);
        foreach ($companies as $company) {
            $urls[] = [
                'loc' => $this->generateUrl('public_company_detail', ['id' => $company->getId()]),
                'changefreq' => 'monthly',
                'priority' => '0.5',
                'lastmod' => $company->getUpdatedAt() ? $company->getUpdatedAt()->format('Y-m-d') : (new \DateTime())->format('Y-m-d')
            ];
        }

        // Categories  
        $categories = $categoriesRepo->findBy(['isActive' => true]);
        foreach ($categories as $category) {
            $urls[] = [
                'loc' => $this->generateUrl('public_products', ['category' => $category->getId()]),
                'changefreq' => 'weekly',
                'priority' => '0.7'
            ];
        }

        // Bids
        $bids = $bidsRepo->findBy(['status' => 'approved'], null, 500);
        foreach ($bids as $bid) {
            $urls[] = [
                'loc' => $this->generateUrl('public_bid_detail', ['id' => $bid->getId()]),
                'changefreq' => 'daily',
                'priority' => '0.6',
                'lastmod' => $bid->getUpdatedAt() ? $bid->getUpdatedAt()->format('Y-m-d') : (new \DateTime())->format('Y-m-d')
            ];
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/xml');
        
        return $this->render('sitemap.xml.twig', [
            'urls' => $urls
        ], $response);
    }
}
