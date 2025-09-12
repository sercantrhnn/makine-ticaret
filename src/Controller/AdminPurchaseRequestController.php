<?php

namespace App\Controller;

use App\Entity\PurchaseRequest;
use App\Repository\PurchaseRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/purchase-requests')]
#[IsGranted('ROLE_ADMIN')]
class AdminPurchaseRequestController extends AbstractController
{
    public function __construct(private PurchaseRequestRepository $repo, private EntityManagerInterface $em) {}

    #[Route('', name: 'admin_purchase_requests')]
    public function index(): Response
    {
        $items = $this->repo->findBy([], ['date' => 'DESC']);
        return $this->render('admin/purchase_requests/index.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/{id}/approve', name: 'admin_purchase_request_approve')]
    public function approve(PurchaseRequest $pr): Response
    {
        $pr->setStatus('approved');
        $this->em->flush();
        $this->addFlash('success', 'Talep onaylandı.');
        return $this->redirectToRoute('admin_purchase_requests');
    }

    #[Route('/{id}/reject', name: 'admin_purchase_request_reject')]
    public function reject(PurchaseRequest $pr): Response
    {
        $pr->setStatus('rejected');
        $this->em->flush();
        $this->addFlash('success', 'Talep reddedildi.');
        return $this->redirectToRoute('admin_purchase_requests');
    }
}


