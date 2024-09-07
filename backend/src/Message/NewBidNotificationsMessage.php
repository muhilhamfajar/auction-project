<?php

namespace App\Message;

class NewBidNotificationsMessage
{
    public function __construct(
        public int $itemId,
        public int $newBidId
    ) {
    }
}
