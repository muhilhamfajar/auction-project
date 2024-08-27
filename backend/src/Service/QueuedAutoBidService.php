<?php

namespace App\Service;

use App\Entity\Item;
use App\Entity\User;
use App\Entity\Bid;
use App\Entity\BidConfig;
use App\Repository\AutoBidRepository;
use App\Repository\BidConfigRepository;
use App\Repository\BidRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class QueuedAutoBidService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AutoBidRepository $autoBidRepository,
        private BidConfigRepository $bidConfigRepository,
        private BidRepository $bidRepository,
        private BidService $bidService,
        private LoggerInterface $logger,
        private NotificationService $notificationService
    ) {
    }

    public function processAutoBids(Item $item): void
    {
        $autoBids = $this->autoBidRepository->findBy(['item' => $item], ['updatedAt' => 'ASC']);

        foreach ($autoBids as $autoBid) {
            $this->processUserAutoBid($item, $autoBid->getUser());
        }
    }

    private function processUserAutoBid(Item $item, User $user): void
    {
        $bidConfig = $this->getBidConfig($user, $item);
        if (! $bidConfig) {
            return;
        }

        $highestBid = $this->bidRepository->findHighestBidForItem($item);
        if ($this->isUserHighestBidder($highestBid, $user)) {
            return;
        }

        $newBidAmount = $this->calculateNewBidAmount($highestBid, $item);
        $potentialReservedAmount = $this->calculatePotentialReservedAmount($bidConfig, $newBidAmount, $user, $item);

        if ($this->exceedsMaxBidAmount($potentialReservedAmount, $bidConfig, $user, $item)) {
            return;
        }

        $this->placeBidAndUpdateConfig($item, $user, $newBidAmount, $potentialReservedAmount, $bidConfig);
    }

    private function getBidConfig(User $user, Item $item): ?BidConfig
    {
        $bidConfig = $this->bidConfigRepository->findOneBy(['user' => $user]);
        if (! $bidConfig) {
            $this->logger->info("Auto-bid skipped for user {$user->getId()} on item {$item->getId()} due to missing config.");
        }
        return $bidConfig;
    }

    private function isUserHighestBidder($highestBid, User $user): bool
    {
        if ($highestBid && $highestBid->getBidder() === $user) {
            $this->logger->info("Auto-bid skipped for user {$user->getId()} as they already have the highest bid.");
            return true;
        }
        return false;
    }

    private function calculateNewBidAmount($highestBid, Item $item): float
    {
        return $highestBid ? $highestBid->getAmount() + 1 : $item->getStartingPrice() + 1;
    }

    private function calculatePotentialReservedAmount(BidConfig $bidConfig, float $newBidAmount, User $user, Item $item): float
    {
        $userLastBid = $this->bidRepository->findOneBy(['item' => $item, 'bidder' => $user], ['amount' => 'DESC']);
        return $bidConfig->getReservedAmount() + ($newBidAmount - ($userLastBid ? $userLastBid->getAmount() : 0));
    }

    private function exceedsMaxBidAmount(float $potentialReservedAmount, BidConfig $bidConfig, User $user, Item $item): bool
    {
        if ($potentialReservedAmount > $bidConfig->getMaxBidAmount()) {
            $this->logger->info("Auto-bid skipped for user {$user->getId()} on item {$item->getId()} as new bid would exceed max bid amount.");
            return true;
        }
        return false;
    }

    private function placeBidAndUpdateConfig(Item $item, User $user, float $newBidAmount, float $potentialReservedAmount, BidConfig $bidConfig): void
    {
        $bid = $this->createBid($item, $user, $newBidAmount);

        try {
            $this->bidService->placeBid($bid);
            $this->updateBidConfig($bidConfig, $potentialReservedAmount);
            $this->checkAndSendAlertNotification($bidConfig);
            $this->logger->info("Auto-bid placed for user {$user->getId()} on item {$item->getId()} for amount {$newBidAmount}. New reserved amount: {$potentialReservedAmount}");
        } catch (\Exception $e) {
            $this->logger->error("Failed to place auto-bid for user {$user->getId()} on item {$item->getId()}: " . $e->getMessage());
        }
    }

    private function createBid(Item $item, User $user, float $amount): Bid
    {
        $bid = new Bid();
        $bid->setBidder($user);
        $bid->setItem($item);
        $bid->setAmount($amount);
        $bid->setBidTime(new \DateTime());
        $bid->setIsAutoBid(true);
        return $bid;
    }

    private function updateBidConfig(BidConfig $bidConfig, float $reservedAmount): void
    {
        $bidConfig->setReservedAmount($reservedAmount);
        $this->entityManager->persist($bidConfig);
        $this->entityManager->flush();
    }

    private function checkAndSendAlertNotification(BidConfig $bidConfig): void
    {
        $reservedPercentage = ($bidConfig->getReservedAmount() / $bidConfig->getMaxBidAmount()) * 100;

        if ($reservedPercentage >= $bidConfig->getBidAlertPercentage() && ! $bidConfig->isAlertSent()) {
            $this->notificationService->createBidAlertNotification(
                $bidConfig->getUser(),
                $bidConfig->getReservedAmount(),
                $bidConfig->getMaxBidAmount()
            );
            $bidConfig->setAlertSent(true);
            $this->entityManager->persist($bidConfig);
            $this->entityManager->flush();
        }

        // Check for alert percentage notification
        if ($reservedPercentage >= $bidConfig->getBidAlertPercentage() && ! $bidConfig->isAlertSent()) {
            $this->notificationService->createBidAlertNotification(
                $bidConfig->getUser(),
                $bidConfig->getReservedAmount(),
                $bidConfig->getMaxBidAmount()
            );
            $bidConfig->setAlertSent(true);
            $this->entityManager->persist($bidConfig);
            $this->entityManager->flush();
        }

        // Check for 100% reserved notification
        if ($reservedPercentage >= 100) {
            $this->notificationService->createBidAlertNotification(
                $bidConfig->getUser(),
                $bidConfig->getReservedAmount(),
                $bidConfig->getMaxBidAmount()
            );
            $bidConfig->setAlertSent(true);
            $this->entityManager->persist($bidConfig);
            $this->entityManager->flush();
        }
    }
}
