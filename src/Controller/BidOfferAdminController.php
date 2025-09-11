<?php

namespace App\Controller;

use App\Entity\Bids;
use App\Entity\BidOffer;
use App\Repository\BidOfferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/bids')]
#[IsGranted('ROLE_SUBSCRIBER')]
class BidOfferAdminController extends AbstractController
{
    public function __construct(private BidOfferRepository $bidOfferRepository) {}

    #[Route('/{id}/offers', name: 'admin_bid_offers', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function list(Bids $bid): Response
    {
        $user = $this->getUser();
        // Yetki: admin veya bid'in sahibi (şirket sahibi)
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            if (!$bid->getCompany() || $bid->getCompany()->getOwner() !== $user) {
                throw $this->createAccessDeniedException();
            }
        }

        $offers = $this->bidOfferRepository->findBy(['bid' => $bid], ['createdAt' => 'DESC']);

        return $this->render('admin/bid_offers/index.html.twig', [
            'bid' => $bid,
            'offers' => $offers,
        ]);
    }

    #[Route('/{id}/offers/{offerId}', name: 'admin_bid_offer_show', methods: ['GET'], requirements: ['id' => '\\d+', 'offerId' => '\\d+'])]
    public function show(Bids $bid, int $offerId): Response
    {
        $user = $this->getUser();
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            if (!$bid->getCompany() || $bid->getCompany()->getOwner() !== $user) {
                throw $this->createAccessDeniedException();
            }
        }

        $offer = $this->bidOfferRepository->find($offerId);
        if (!$offer || $offer->getBid() !== $bid) {
            throw $this->createNotFoundException();
        }

        return $this->render('admin/bid_offers/show.html.twig', [
            'bid' => $bid,
            'offer' => $offer,
        ]);
    }
}


