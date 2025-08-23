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
}
