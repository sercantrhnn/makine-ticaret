<?php

namespace App\Controller;

use App\Entity\Companies;
use App\Form\CompaniesType;
use App\Repository\CompaniesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/companies')]
#[IsGranted('ROLE_ADMIN', message: 'Bu sayfaya erişim için admin yetkisi gereklidir.')]
class CompaniesController extends AbstractController
{
    #[Route('/', name: 'companies_index', methods: ['GET'])]
    public function index(Request $request, CompaniesRepository $companiesRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 15; // Sayfa başına kayıt sayısı
        $search = $request->query->get('search', '');
        
        $companies = $companiesRepository->findWithPaginationAndSearch($page, $limit, $search);
        $totalCompanies = $companiesRepository->countWithSearch($search);
        $totalPages = ceil($totalCompanies / $limit);
        
        return $this->render('admin/companies/index.html.twig', [
            'companies' => $companies,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCompanies' => $totalCompanies,
            'search' => $search,
            'limit' => $limit,
        ]);
    }

    #[Route('/new', name: 'companies_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $company = new Companies();
        $form = $this->createForm(CompaniesType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Dosya yükleme işlemleri
            $this->handleFileUploads($form, $company);
            
            $entityManager->persist($company);
            $entityManager->flush();

            $this->addFlash('success', 'Firma başarıyla eklendi.');
            return $this->redirectToRoute('companies_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/companies/new.html.twig', [
            'company' => $company,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'companies_show', methods: ['GET'])]
    public function show(Companies $company): Response
    {
        return $this->render('admin/companies/show.html.twig', [
            'company' => $company,
        ]);
    }

    #[Route('/{id}/edit', name: 'companies_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Companies $company, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CompaniesType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Dosya yükleme işlemleri
            $this->handleFileUploads($form, $company);
            
            $entityManager->flush();

            $this->addFlash('success', 'Firma başarıyla güncellendi.');
            return $this->redirectToRoute('companies_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/companies/edit.html.twig', [
            'company' => $company,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'companies_delete', methods: ['POST'])]
    public function delete(Request $request, Companies $company, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$company->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($company);
            $entityManager->flush();
            $this->addFlash('success', 'Firma başarıyla silindi.');
        }

        return $this->redirectToRoute('companies_index', [], Response::HTTP_SEE_OTHER);
    }
    
    /**
     * Dosya yükleme işlemlerini gerçekleştir
     */
    private function handleFileUploads($form, Companies $company): void
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/';
        $configurations = [
            [
                'fileField' => 'logoFile',
                'removeField' => 'removeLogo',
                'getter' => 'getLogoPath',
                'setter' => 'setLogoPath',
                'subDir' => 'logos',
                'prefix' => 'logo',
            ],
            [
                'fileField' => 'backgroundPhotoFile',
                'removeField' => 'removeBackgroundPhoto',
                'getter' => 'getBackgroundPhotoPath',
                'setter' => 'setBackgroundPhotoPath',
                'subDir' => 'backgrounds',
                'prefix' => 'background',
            ],
            [
                'fileField' => 'catalogFile',
                'removeField' => 'removeCatalog',
                'getter' => 'getCatalogPath',
                'setter' => 'setCatalogPath',
                'subDir' => 'catalogs',
                'prefix' => 'catalog',
            ],
            [
                'fileField' => 'certificateFile',
                'removeField' => 'removeCertificate',
                'getter' => 'getCertificatePath',
                'setter' => 'setCertificatePath',
                'subDir' => 'certificates',
                'prefix' => 'certificate',
            ],
        ];

        foreach ($configurations as $configuration) {
            $currentPath = $company->{$configuration['getter']}();
            $removeRequested = $form->has($configuration['removeField']) ? $form->get($configuration['removeField'])->getData() : false;

            if ($removeRequested && $currentPath) {
                $this->deletePhysicalFile($currentPath);
                $company->{$configuration['setter']}(null);
                $currentPath = null;
            }

            $uploadedFile = $form->get($configuration['fileField'])->getData();
            if ($uploadedFile) {
                if ($currentPath) {
                    $this->deletePhysicalFile($currentPath);
                }

                $fileName = $this->generateUniqueFileName($uploadedFile, $configuration['prefix']);
                $uploadedFile->move($uploadDir . $configuration['subDir'], $fileName);
                $company->{$configuration['setter']}('uploads/' . $configuration['subDir'] . '/' . $fileName);
            }
        }
    }
    
    /**
     * Unique dosya ismi oluştur
     */
    private function generateUniqueFileName($file, $prefix): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        
        return $prefix . '_' . $timestamp . '_' . $random . '.' . $extension;
    }

    /**
     * Fiziksel dosyayı diskte sil
     */
    private function deletePhysicalFile(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $fullPath = $this->getParameter('kernel.project_dir') . '/public/' . ltrim($relativePath, '/');

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
