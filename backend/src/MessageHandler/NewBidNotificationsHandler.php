<?php

namespace App\MessageHandler;

use App\Message\NewBidNotificationsMessage;
use App\Repository\BidRepository;
use App\Repository\ItemRepository;
use App\Service\AuctionNotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NewBidNotificationsHandler
{
    public function __construct(
        private ItemRepository $itemRepository,
        private BidRepository $bidRepository,
        private AuctionNotificationService $notificationService,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(NewBidNotificationsMessage $message): void
    {
        $item = $this->itemRepository->find($message->itemId);
        $newBid = $this->bidRepository->find($message->newBidId);

        if (! $item || ! $newBid) {
            return;
        }

        $bids = $this->bidRepository->findUniqueBiddersByItem($item, $newBid->getBidder());

        foreach ($bids as $bid) {
            $this->notificationService->sendNewBidNotification($item, $newBid, $bid);
        }
    }
}
