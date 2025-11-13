<?php

namespace App\Controller;

use App\Entity\BidMessage;
use App\Entity\BidOffer;
use App\Entity\PurchaseRequest;
use App\Entity\PurchaseRequestMessage;
use App\Form\BidMessageType;
use App\Repository\BidMessageRepository;
use App\Repository\BidOfferRepository;
use App\Repository\PurchaseRequestRepository;
use App\Repository\PurchaseRequestMessageRepository;
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
        private PurchaseRequestRepository $purchaseRequestRepo,
        private PurchaseRequestMessageRepository $purchaseRequestMessageRepo,
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

        // Kullanıcının alım talepleri:
        // 1. E-posta adresine göre (alım talebi sahibi)
        // 2. Mesaj gönderdiği alım talepleri (subscriber olarak)
        $purchaseRequestsByEmail = $this->purchaseRequestRepo->createQueryBuilder('pr')
            ->andWhere('pr.email = :email')
            ->andWhere('pr.status = :status')
            ->setParameter('email', $user->getEmail())
            ->setParameter('status', 'approved')
            ->getQuery()->getResult();

        // Kullanıcının mesaj gönderdiği alım talepleri
        $purchaseRequestsByMessages = $this->purchaseRequestRepo->createQueryBuilder('pr')
            ->innerJoin('App\Entity\PurchaseRequestMessage', 'm', 'WITH', 'm.purchaseRequest = pr')
            ->andWhere('m.sender = :user')
            ->andWhere('pr.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'approved')
            ->getQuery()->getResult();

        // İki listeyi birleştir ve tekrarları ID'ye göre kaldır
        $purchaseRequestsMap = [];
        foreach ($purchaseRequestsByEmail as $pr) {
            $purchaseRequestsMap[$pr->getId()] = $pr;
        }
        foreach ($purchaseRequestsByMessages as $pr) {
            $purchaseRequestsMap[$pr->getId()] = $pr;
        }
        $purchaseRequests = array_values($purchaseRequestsMap);

        // Tarihe göre sırala
        usort($purchaseRequests, function($a, $b) {
            return $b->getDate() <=> $a->getDate();
        });

        return $this->render('messages/inbox.html.twig', [
            'offers' => $offers,
            'purchaseRequests' => $purchaseRequests,
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

    #[Route('/messages/purchase-request/{id}', name: 'user_purchase_request_thread', requirements: ['id' => '\\d+'])]
    public function purchaseRequestThread(Request $request, PurchaseRequest $pr): Response
    {
        $user = $this->getUser();
        
        // Kullanıcı alım talebi sahibi mi kontrol et
        $isOwner = $pr->getEmail() === $user->getEmail();
        
        // Kullanıcı bu alım talebine mesaj göndermiş mi kontrol et
        $hasSentMessage = $this->purchaseRequestMessageRepo->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.purchaseRequest = :pr')
            ->andWhere('m.sender = :user')
            ->setParameter('pr', $pr)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult() > 0;
        
        // Erişim kontrolü: Alım talebi sahibi veya mesaj gönderen subscriber olmalı
        if (!$isOwner && !$hasSentMessage) {
            throw $this->createAccessDeniedException();
        }

        // Mesajları filtrele:
        // - Alım talebi sahibi ise: Tüm mesajları göster
        // - Subscriber ise: Sadece kendi gönderdiği mesajları göster
        if ($isOwner) {
            $messages = $this->purchaseRequestMessageRepo->findBy(
                ['purchaseRequest' => $pr], 
                ['createdAt' => 'ASC']
            );
        } else {
            $messages = $this->purchaseRequestMessageRepo->createQueryBuilder('m')
                ->where('m.purchaseRequest = :pr')
                ->andWhere('m.sender = :user')
                ->setParameter('pr', $pr)
                ->setParameter('user', $user)
                ->orderBy('m.createdAt', 'ASC')
                ->getQuery()
                ->getResult();
        }

        $message = new PurchaseRequestMessage();
        $message->setPurchaseRequest($pr);
        $message->setSender($user);
        $message->setReceiverEmail($pr->getEmail());

        $form = $this->createForm(\App\Form\PurchaseRequestMessageType::class, $message);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($message);
            $this->em->flush();
            return $this->redirectToRoute('user_purchase_request_thread', ['id' => $pr->getId()]);
        }

        return $this->render('messages/purchase_request_thread.html.twig', [
            'pr' => $pr,
            'messages' => $messages,
            'form' => $form,
        ]);
    }
}


