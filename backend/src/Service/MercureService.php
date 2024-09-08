<?php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercureService
{
    public function __construct(private HubInterface $hub)
    {
    }

    public function publishUpdate(string $topic, array $data): void
    {
        $update = new Update(
            $topic,
            json_encode($data)
        );
        $this->hub->publish($update);
    }
}
