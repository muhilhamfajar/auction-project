<?php

namespace App\Service;

use App\Entity\BaseEntity;
use App\Entity\Bid;
use App\Entity\Item;
use App\Entity\User;
use App\Repository\BidRepository;
use Symfony\Component\HttpFoundation\Request;

class UserBidService
{
    public function __construct(
        private BidRepository $bidRepository,
        private AuctionBillService $auctionBillService
    ) {
    }

    public function getCurrentBids(Request $request, User $user): array
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $sort = $request->query->get('sort', 'item.auctionEndTime');
        $order = $request->query->get('order', 'ASC');
        $search = $request->query->get('q');

        $result = $this->bidRepository->findCurrentBidsForUser($user, $search, $sort, $order);
        $mappedResult = $this->mapBidsData($result);

        return $this->paginateResults($mappedResult, $page, $limit);
    }

    public function getAwardedItems(Request $request, User $user): array
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $sort = $request->query->get('sort', 'item.auctionEndTime');
        $order = $request->query->get('order', 'DESC');
        $search = $request->query->get('q');

        $result = $this->bidRepository->findAwardedItemsForUser($user, $search, $sort, $order);
        $mappedResult = $this->mapBidsData($result, true);

        return $this->paginateResults($mappedResult, $page, $limit);
    }

    private function mapBidsData(array $bids, bool $includeBillUrl = false): array
    {
        return array_map(function ($bidData) use ($includeBillUrl) {
            if ($bidData instanceof Bid) {
                $bid = $bidData;
                $item = $bid->getItem();
            } elseif (is_array($bidData) && isset($bidData[0]) && $bidData[0] instanceof Bid) {
                $bid = $bidData[0];
                $item = $bid->getItem();
            } else {
                // Handle unexpected data type
                throw new \InvalidArgumentException('Invalid bid data type');
            }

            $mappedBid = [
            'id' => $bid->getId(),
            'amount' => $bid->getAmount(),
            'bidTime' => $bid->getBidTime()->format('c'),
            'status' => $bid->getStatus(),
            'item' => [
                'id' => $item->getId(),
                'uuid' => $item->getUuid(),
                'name' => $item->getName(),
                'status' => $item->getStatus(),
                'auctionEndTime' => $item->getAuctionEndTime(),
            ]
            ];

            if ($includeBillUrl && $bid->getStatus() === Bid::STATUS_WON) {
                $mappedBid['billUrl'] = $this->auctionBillService->generateBillUrl($bid);
            }

            return $mappedBid;
        }, $bids);
    }


    private function paginateResults(array $results, int $page, int $limit): array
    {
        $totalItems = count($results);
        $pagesCount = ceil($totalItems / $limit);
        $offset = ($page - 1) * $limit;

        return [
            'totalItems' => $totalItems,
            'itemsPerPage' => $limit,
            'currentPage' => $page,
            'totalPages' => $pagesCount,
            'data' => array_slice($results, $offset, $limit),
        ];
    }
}
