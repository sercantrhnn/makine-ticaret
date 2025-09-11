<?php

namespace App\Controller;

use App\Entity\BidMessage;
use App\Entity\BidOffer;
use App\Form\BidMessageType;
use App\Repository\BidMessageRepository;
use App\Repository\BidOfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class BidMessageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private BidMessageRepository $messageRepo,
        private BidOfferRepository $offerRepo,
    ) {}

    #[Route('/admin/bid-offers/{id}/messages', name: 'admin_bid_offer_messages', requirements: ['id' => '\\d+'])]
    public function adminThread(Request $request, BidOffer $offer): Response
    {
        // admin veya ihale sahibi olmalı
        $user = $this->getUser();
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            if ($offer->getOwner() !== $user) {
                throw $this->createAccessDeniedException();
            }
        }

        $messages = $this->messageRepo->findBy(['offer' => $offer], ['createdAt' => 'ASC']);
        $message = new BidMessage();
        $message->setOffer($offer);
        $message->setSender($user);
        $message->setReceiver($offer->getBidder());

        $form = $this->createForm(BidMessageType::class, $message);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($message);
            $this->em->flush();
            return $this->redirectToRoute('admin_bid_offer_messages', ['id' => $offer->getId()]);
        }

        return $this->render('admin/bid_offers/messages.html.twig', [
            'offer' => $offer,
            'messages' => $messages,
            'form' => $form,
        ]);
    }

    #[Route('/messages', name: 'user_messages_inbox')]
    public function inbox(): Response
    {
        $user = $this->getUser();
        // Kullanıcının dahil olduğu tüm teklifler
        $offers = $this->offerRepo->createQueryBuilder('o')
            ->leftJoin('o.bid', 'b')
            ->addSelect('b')
            ->andWhere('o.bidder = :u OR o.owner = :u')
            ->setParameter('u', $user)
            ->orderBy('o.updatedAt', 'DESC')
            ->getQuery()->getResult();

        return $this->render('messages/inbox.html.twig', [
            'offers' => $offers,
        ]);
    }

    #[Route('/messages/offer/{id}', name: 'user_messages_thread', requirements: ['id' => '\\d+'])]
    public function userThread(Request $request, BidOffer $offer): Response
    {
        $user = $this->getUser();
        if ($offer->getBidder() !== $user && $offer->getOwner() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $messages = $this->messageRepo->findBy(['offer' => $offer], ['createdAt' => 'ASC']);
        $message = new BidMessage();
        $message->setOffer($offer);
        $message->setSender($user);
        $message->setReceiver($offer->getBidder() === $user ? $offer->getOwner() : $offer->getBidder());

        $form = $this->createForm(BidMessageType::class, $message);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($message);
            $this->em->flush();
            return $this->redirectToRoute('user_messages_thread', ['id' => $offer->getId()]);
        }

        return $this->render('messages/thread.html.twig', [
            'offer' => $offer,
            'messages' => $messages,
            'form' => $form,
        ]);
    }
}


