<?php

namespace App\Controller;

use App\Entity\Bids;
use App\Entity\BidOffer;
use App\Form\BidOfferType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PublicBidOfferController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/bid/{id}/offer', name: 'public_bid_offer', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_USER')]
    public function offer(Request $request, Bids $bid): Response
    {
        $user = $this->getUser();
        // İhale sahibinin kendisi teklif veremesin
        if ($bid->getCompany() && $bid->getCompany()->getOwner() && $bid->getCompany()->getOwner() === $user) {
            $this->addFlash('error', 'Kendi ihale ilanınıza teklif veremezsiniz.');
            return $this->redirectToRoute('public_bid_detail', ['id' => $bid->getId()]);
        }

        $offer = new BidOffer();
        $offer->setBid($bid);
        $offer->setProduct($bid->getProduct());
        if ($bid->getCompany() && $bid->getCompany()->getOwner()) {
            $offer->setOwner($bid->getCompany()->getOwner());
        }
        $offer->setBidder($user);

        $form = $this->createForm(BidOfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$offer->getContactEmail() && $user->getEmail()) {
                $offer->setContactEmail($user->getEmail());
            }
            $this->em->persist($offer);
            $this->em->flush();

            $this->addFlash('success', 'Teklifiniz gönderildi.');
            return $this->redirectToRoute('public_bid_detail', ['id' => $bid->getId()]);
        }

        return $this->render('public/bid_offer.html.twig', [
            'bid' => $bid,
            'form' => $form,
        ]);
    }
}


