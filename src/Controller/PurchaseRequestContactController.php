<?php

namespace App\Controller;

use App\Entity\PurchaseRequest;
use App\Entity\PurchaseRequestMessage;
use App\Form\PurchaseRequestMessageType;
use App\Repository\PurchaseRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/purchase-request')]
#[IsGranted('ROLE_SUBSCRIBER')]
class PurchaseRequestContactController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em, private PurchaseRequestRepository $repo) {}

    #[Route('/{id}/contact', name: 'purchase_request_contact', requirements: ['id' => '\d+'])]
    public function contact(Request $request, PurchaseRequest $pr): Response
    {
        $message = new PurchaseRequestMessage();
        $message->setPurchaseRequest($pr);
        $message->setSender($this->getUser());
        $message->setReceiverEmail($pr->getEmail());

        $form = $this->createForm(PurchaseRequestMessageType::class, $message);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($message);
            $this->em->flush();
            $this->addFlash('success', 'Mesajınız gönderildi.');
            return $this->redirectToRoute('purchase_request_contact', ['id' => $pr->getId()]);
        }

        // Sadece mevcut kullanıcının gönderdiği mesajları göster
        // Her kullanıcı kendi mesajlaşma thread'ini görmeli
        $currentUser = $this->getUser();
        $messages = $this->em->getRepository(PurchaseRequestMessage::class)->createQueryBuilder('m')
            ->where('m.purchaseRequest = :pr')
            ->andWhere('m.sender = :user')
            ->setParameter('pr', $pr)
            ->setParameter('user', $currentUser)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('public/purchase_request/contact.html.twig', [
            'pr' => $pr,
            'messages' => $messages,
            'form' => $form->createView(),
        ]);
    }
}
