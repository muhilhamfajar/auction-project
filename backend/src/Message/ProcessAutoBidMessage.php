<?php

namespace App\Message;

class ProcessAutoBidMessage
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
