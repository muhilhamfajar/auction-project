<?php

namespace App\MessageHandler;

use App\Message\CloseExpiredAuctionMessage;
use App\Service\ItemService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CloseExpiredAuctionHandler
{
    public function __construct(
        private ItemService $itemService
    ) {
    }

    public function __invoke(CloseExpiredAuctionMessage $message): void
    {
        $this->itemService->closeExpiredAuction($message->getItemId());
    }
}
