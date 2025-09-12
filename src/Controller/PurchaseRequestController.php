<?php

namespace App\Controller;

use App\Entity\PurchaseRequest;
use App\Form\PurchaseRequestType;
use App\Repository\PurchaseRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PurchaseRequestController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private PurchaseRequestRepository $repo) {}

    #[Route('/purchase-request/new', name: 'purchase_request_new')]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $pr = new PurchaseRequest();
        if ($this->getUser()) {
            $pr->setEmail($this->getUser()->getEmail());
        }

        $form = $this->createForm(PurchaseRequestType::class, $pr);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $pr->setStatus('pending');
            $this->em->persist($pr);
            $this->em->flush();
            $this->addFlash('success', 'Alım talebiniz alındı, onay sonrası yayınlanacaktır.');
            return $this->redirectToRoute('public_home');
        }

        return $this->render('public/purchase_request/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}


