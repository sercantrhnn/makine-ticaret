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

#[Route('/admin/companies')]
class CompaniesController extends AbstractController
{
    #[Route('/', name: 'companies_index', methods: ['GET'])]
    public function index(CompaniesRepository $companiesRepository): Response
    {
        return $this->render('admin/companies/index.html.twig', [
            'companies' => $companiesRepository->findAll(),
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
        
        // Logo yükleme
        $logoFile = $form->get('logoFile')->getData();
        if ($logoFile) {
            // Eski logo varsa sil
            if ($company->getLogoPath() && file_exists($this->getParameter('kernel.project_dir') . '/public/' . $company->getLogoPath())) {
                unlink($this->getParameter('kernel.project_dir') . '/public/' . $company->getLogoPath());
            }
            
            $logoFileName = $this->generateUniqueFileName($logoFile, 'logo');
            $logoFile->move($uploadDir . 'logos', $logoFileName);
            $company->setLogoPath('uploads/logos/' . $logoFileName);
        }
        
        // Arka plan fotoğrafı yükleme
        $backgroundFile = $form->get('backgroundPhotoFile')->getData();
        if ($backgroundFile) {
            // Eski arka plan varsa sil
            if ($company->getBackgroundPhotoPath() && file_exists($this->getParameter('kernel.project_dir') . '/public/' . $company->getBackgroundPhotoPath())) {
                unlink($this->getParameter('kernel.project_dir') . '/public/' . $company->getBackgroundPhotoPath());
            }
            
            $backgroundFileName = $this->generateUniqueFileName($backgroundFile, 'background');
            $backgroundFile->move($uploadDir . 'backgrounds', $backgroundFileName);
            $company->setBackgroundPhotoPath('uploads/backgrounds/' . $backgroundFileName);
        }
        
        // Katalog yükleme
        $catalogFile = $form->get('catalogFile')->getData();
        if ($catalogFile) {
            // Eski katalog varsa sil
            if ($company->getCatalogPath() && file_exists($this->getParameter('kernel.project_dir') . '/public/' . $company->getCatalogPath())) {
                unlink($this->getParameter('kernel.project_dir') . '/public/' . $company->getCatalogPath());
            }
            
            $catalogFileName = $this->generateUniqueFileName($catalogFile, 'catalog');
            $catalogFile->move($uploadDir . 'catalogs', $catalogFileName);
            $company->setCatalogPath('uploads/catalogs/' . $catalogFileName);
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
}
