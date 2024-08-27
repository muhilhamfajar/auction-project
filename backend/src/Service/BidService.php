<?php

namespace App\Service;

use App\Entity\Bid;
use App\Repository\BidRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BidService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BidRepository $bidRepository,
        private AsyncAutoBidService $asyncAutoBidService
    ) {
    }

    public function placeBid(Bid $bid): Bid
    {
        $this->validateBid($bid);

        $this->entityManager->persist($bid);
        $this->entityManager->flush();

        $this->asyncAutoBidService->triggerAutoBids($bid->getItem());

        return $bid;
    }

    private function validateBid(Bid $bid): void
    {
        if ($bid->getBidTime() > $bid->getItem()->getAuctionEndTime()) {
            throw new BadRequestHttpException('Bidding has closed for this item.');
        }

        $highestBid = $this->bidRepository->findHighestBidForItem($bid->getItem());
        if ($highestBid && $bid->getAmount() <= $highestBid->getAmount()) {
            throw new BadRequestHttpException('Bid amount must be higher than the current highest bid.');
        }

        if ($bid->getAmount() <= $bid->getItem()->getStartingPrice()) {
            throw new BadRequestHttpException('Bid amount must be higher than the starting price.');
        }
    }
}
