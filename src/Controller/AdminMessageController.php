<?php

namespace App\Controller;

use App\Repository\BidOfferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/messages')]
#[IsGranted('ROLE_SUBSCRIBER')]
class AdminMessageController extends AbstractController
{
    public function __construct(private BidOfferRepository $offerRepo) {}

    #[Route('', name: 'admin_messages_inbox', methods: ['GET'])]
    public function inbox(): Response
    {
        $user = $this->getUser();

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $offers = $this->offerRepo->createQueryBuilder('o')
                ->leftJoin('o.bid', 'b')->addSelect('b')
                ->leftJoin('o.product', 'p')->addSelect('p')
                ->leftJoin('o.bidder', 'u')->addSelect('u')
                ->orderBy('o.updatedAt', 'DESC')
                ->getQuery()->getResult();
        } else {
            $offers = $this->offerRepo->createQueryBuilder('o')
                ->leftJoin('o.bid', 'b')->addSelect('b')
                ->leftJoin('o.product', 'p')->addSelect('p')
                ->leftJoin('o.bidder', 'u')->addSelect('u')
                ->andWhere('o.owner = :u')
                ->setParameter('u', $user)
                ->orderBy('o.updatedAt', 'DESC')
                ->getQuery()->getResult();
        }

        return $this->render('admin/messages/inbox.html.twig', [
            'offers' => $offers,
        ]);
    }
}


