<?php

namespace App\Service;

use App\Entity\Bid;
use App\Entity\Item;
use App\Repository\ItemRepository;
use App\Repository\BidRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ItemService
{
    public function __construct(
        private ItemRepository $itemRepository,
        private BidRepository $bidRepository,
        private PaginationService $paginationService,
        private EntityManagerInterface $entityManager,
        private AuctionNotificationService $auctionNotificationService
    ) {
    }

    public function getPaginatedItems(Request $request): array
    {
        $searchTerms = ['name', 'description'];
        $paginatedResults = $this->paginationService->paginate($this->itemRepository, $request, [], $searchTerms);

        $highestBids = $this->bidRepository->findHighestBidsForItems($paginatedResults['data']);

        $itemsWithHighestBids = array_map(function ($item) use ($highestBids) {
            return [
                'item' => $item,
                'highestBid' => $highestBids[$item->getId()] ?? null,
            ];
        }, $paginatedResults['data']);

        $paginatedResults['data'] = $itemsWithHighestBids;

        return $paginatedResults;
    }

    public function getItemWithHighestBid(string $uuid): ?array
    {
        $item = $this->itemRepository->findOneBy(['uuid' => $uuid]);

        if (! $item) {
            return null;
        }

        $highestBid = $this->bidRepository->findHighestBidForItem($item);

        return [
            'item' => $item,
            'highestBid' => $highestBid,
        ];
    }

    public function createItem(array $data): Item
    {
        $item = new Item();
        $this->updateItemFromData($item, $data);

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }

    public function updateItem(Item $item, array $data): Item
    {
        $this->updateItemFromData($item, $data);
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item;
    }

    public function deleteItem(Item $item): void
    {
        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    private function updateItemFromData(Item $item, array $data): void
    {
        if (isset($data['name'])) {
            $item->setName($data['name']);
        }
        if (isset($data['description'])) {
            $item->setDescription($data['description']);
        }
        if (isset($data['startingPrice'])) {
            $item->setStartingPrice($data['startingPrice']);
        }
        if (isset($data['auctionStartTime'])) {
            $item->setAuctionStartTime(new \DateTime($data['auctionStartTime']));
        }
        if (isset($data['auctionEndTime'])) {
            $item->setAuctionEndTime(new \DateTime($data['auctionEndTime']));
        }
    }

    public function closeExpiredAuction(int $itemId): void
    {
        $item = $this->itemRepository->find($itemId);

        if (! $item || $item->getStatus() !== Item::STATUS_ACTIVE || $item->getAuctionEndTime() > new \DateTime()) {
            return;
        }

        $winningBid = $this->bidRepository->findHighestBidForItem($item);

        if ($winningBid) {
            $this->awardItemToWinner($item, $winningBid);
            $this->auctionNotificationService->sendWinnerNotification($item, $winningBid);
            $this->sendLoserNotifications($item, $winningBid);
        }

        $item->setStatus(Item::STATUS_EXPIRED);
        $this->entityManager->flush();

        // $this->sendAuctionEndedNotifications($item);
    }

    private function awardItemToWinner(Item $item, Bid $winningBid): void
    {
        $winningBid->setStatus(Bid::STATUS_WON);
        $this->entityManager->persist($winningBid);

        $losingBidders = $this->bidRepository->findLosingBids($item, $winningBid);
        foreach ($losingBidders as $bid) {
            $bid->setStatus(Bid::STATUS_LOST);
            $this->entityManager->persist($bid);
        }
    }

    private function sendLoserNotifications(Item $item, Bid $winningBid): void
    {
        $losingBidders = $this->bidRepository->findUniqueLosingBiddersWithHighestBids($item, $winningBid->getBidder());

        foreach ($losingBidders as $losingBidder) {
            $this->auctionNotificationService->sendLoserNotification(
                $item,
                $winningBid,
                $losingBidder
            );
        }
    }

    private function sendAuctionEndedNotifications(Item $item): void
    {
        $bidders = $this->bidRepository->findUniqueBiddersByItem($item);

        foreach ($bidders as $bidder) {
            $highestBid = $this->bidRepository->findHighestBidForItemAndUser($item, $bidder);
            $this->auctionNotificationService->sendAuctionEndedNotification($item, $bidder, $highestBid);
        }
    }
}
