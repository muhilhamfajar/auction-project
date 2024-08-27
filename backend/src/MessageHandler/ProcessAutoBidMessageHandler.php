<?php

namespace App\MessageHandler;

use App\Message\ProcessAutoBidMessage;
use App\Repository\ItemRepository;
use App\Service\QueuedAutoBidService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProcessAutoBidMessageHandler
{
    public function __construct(
        private ItemRepository $itemRepository,
        private QueuedAutoBidService $queuedAutoBidService
    ) {
    }

    public function __invoke(ProcessAutoBidMessage $message)
    {
        $item = $this->itemRepository->find($message->getItemId());
        if (! $item) {
            return;
        }

        $this->queuedAutoBidService->processAutoBids($item);
    }
}
