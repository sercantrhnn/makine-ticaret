<?php

namespace App\Controller;

use App\Entity\Products;
use App\Form\ProductsType;
use App\Entity\Companies;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/products')]
#[IsGranted('ROLE_ADMIN', message: 'Bu sayfaya erişim için admin yetkisi gereklidir.')]
class ProductsController extends AbstractController
{
    #[Route('/', name: 'products_index', methods: ['GET'])]
    public function index(Request $request, ProductsRepository $productsRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 15; // Sayfa başına kayıt sayısı
        $search = $request->query->get('search', '');
        
        $products = $productsRepository->findWithPaginationAndSearch($page, $limit, $search);
        $totalProducts = $productsRepository->countWithSearch($search);
        $totalPages = ceil($totalProducts / $limit);
        
        return $this->render('admin/products/index.html.twig', [
            'products' => $products,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalProducts' => $totalProducts,
            'search' => $search,
            'limit' => $limit,
        ]);
    }

    #[Route('/new', name: 'products_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Products();

        // Eğer bir firma ID'si ile gelindiyse, ürüne önceden set et
        $companyId = $request->query->getInt('company', 0);
        if ($companyId > 0) {
            $company = $entityManager->getRepository(Companies::class)->find($companyId);
            if ($company) {
                $product->setCompany($company);
            }
        }
        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Dosya yükleme işlemleri
            $this->handleFileUploads($form, $product, $entityManager);
            
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Ürün başarıyla eklendi.');
            return $this->redirectToRoute('products_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/products/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'products_show', methods: ['GET'])]
    public function show(Products $product): Response
    {
        return $this->render('admin/products/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'products_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Products $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductsType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Dosya yükleme işlemleri
            $this->handleFileUploads($form, $product, $entityManager);
            
            $entityManager->flush();

            $this->addFlash('success', 'Ürün başarıyla güncellendi.');
            return $this->redirectToRoute('products_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/products/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/images/{id}/delete', name: 'product_images_delete', methods: ['POST'])]
    public function deleteImage(Request $request, \App\Entity\ProductImages $image, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_image_'.$image->getId(), (string) $request->request->get('_token'))) {
            $product = $image->getProduct();
            // Fiziksel dosyayı sil
            if ($image->getImagePath() && file_exists($this->getParameter('kernel.project_dir') . '/public/' . $image->getImagePath())) {
                @unlink($this->getParameter('kernel.project_dir') . '/public/' . $image->getImagePath());
            }
            $entityManager->remove($image);
            $entityManager->flush();
            $this->addFlash('success', 'Fotoğraf silindi.');
            return $this->redirectToRoute('products_edit', ['id' => $product->getId()]);
        }
        $this->addFlash('error', 'Geçersiz istek.');
        return $this->redirectToRoute('products_index');
    }

    #[Route('/videos/{id}/delete', name: 'product_videos_delete', methods: ['POST'])]
    public function deleteVideo(Request $request, \App\Entity\ProductVideos $video, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_video_'.$video->getId(), (string) $request->request->get('_token'))) {
            $product = $video->getProduct();
            // Fiziksel dosyayı sil
            if ($video->getVideoPath() && file_exists($this->getParameter('kernel.project_dir') . '/public/' . $video->getVideoPath())) {
                @unlink($this->getParameter('kernel.project_dir') . '/public/' . $video->getVideoPath());
            }
            $entityManager->remove($video);
            $entityManager->flush();
            $this->addFlash('success', 'Video silindi.');
            return $this->redirectToRoute('products_edit', ['id' => $product->getId()]);
        }
        $this->addFlash('error', 'Geçersiz istek.');
        return $this->redirectToRoute('products_index');
    }

    #[Route('/{id}', name: 'products_delete', methods: ['POST'])]
    public function delete(Request $request, Products $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $entityManager->remove($product);
                $entityManager->flush();
                $this->addFlash('success', 'Ürün başarıyla silindi.');
            } catch (ForeignKeyConstraintViolationException $e) {
                $this->addFlash('error', 'Ürün silinemedi: İlişkili kayıtlar (ör. ihale/teklif) mevcut. Önce ilişkili kayıtları kaldırın.');
            }
        }

        return $this->redirectToRoute('products_index', [], Response::HTTP_SEE_OTHER);
    }
    
    /**
     * Dosya yükleme işlemlerini gerçekleştir
     */
    private function handleFileUploads($form, Products $product, EntityManagerInterface $entityManager): void
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/';
        
        // Fotoğraf yüklemeleri
        $imageFiles = $form->get('imageFiles')->getData();
        if ($imageFiles) {
            foreach ($imageFiles as $imageFile) {
                $imageFileName = $this->generateUniqueFileName($imageFile, 'product_image');
                $imageFile->move($uploadDir . 'product_images', $imageFileName);
                
                // ProductImages entity oluştur
                $productImage = new \App\Entity\ProductImages();
                $productImage->setImagePath('uploads/product_images/' . $imageFileName);
                $productImage->setProduct($product);
                
                $entityManager->persist($productImage);
            }
        }
        
        // Video yüklemeleri
        $videoFiles = $form->get('videoFiles')->getData();
        if ($videoFiles) {
            foreach ($videoFiles as $videoFile) {
                $videoFileName = $this->generateUniqueFileName($videoFile, 'product_video');
                $videoFile->move($uploadDir . 'product_videos', $videoFileName);
                
                // ProductVideos entity oluştur
                $productVideo = new \App\Entity\ProductVideos();
                $productVideo->setVideoPath('uploads/product_videos/' . $videoFileName);
                $productVideo->setProduct($product);
                
                $entityManager->persist($productVideo);
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
}
