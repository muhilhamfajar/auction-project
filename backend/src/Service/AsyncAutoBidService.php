<?php

namespace App\Service;

use App\Entity\AutoBid;
use App\Entity\Item;
use App\Entity\User;
use App\Message\ProcessAutoBidMessage;
use App\Repository\AutoBidRepository;
use App\Repository\BidConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AsyncAutoBidService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private EntityManagerInterface $entityManager,
        private AutoBidRepository $autoBidRepository,
        private BidConfigRepository $bidConfigRepository
    ) {
    }

    public function triggerAutoBids(Item $item): void
    {
        $message = new ProcessAutoBidMessage($item->getId());
        $this->messageBus->dispatch($message);
    }

    public function activateAutoBid(Item $item, User $user): void
    {
        $bidConfig = $this->bidConfigRepository->findOneBy(['user' => $user]);

        if (! $bidConfig) {
            throw new BadRequestHttpException('User has not set up auto bid configuration.');
        }

        $existingAutoBid = $this->autoBidRepository->findOneBy(['item' => $item, 'user' => $user]);

        if (! $existingAutoBid) {
            $autoBid = new AutoBid();
            $autoBid->setItem($item);
            $autoBid->setUser($user);

            $this->entityManager->persist($autoBid);
            $this->entityManager->flush();
        }

        $this->triggerAutoBids($item);
    }

    public function deactivateAutoBid(Item $item, User $user): void
    {
        $autoBid = $this->autoBidRepository->findOneBy(['item' => $item, 'user' => $user]);

        if ($autoBid) {
            $this->entityManager->remove($autoBid);
            $this->entityManager->flush();
        }
    }
}
