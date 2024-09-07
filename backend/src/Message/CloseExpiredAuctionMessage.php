<?php

namespace App\Message;

class CloseExpiredAuctionMessage
{
    public function __construct(
        private int $itemId
    ) {
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }
}
