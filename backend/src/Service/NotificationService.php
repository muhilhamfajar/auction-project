<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository
    ) {
    }

    public function createBidAlertNotification(User $user, float $reservedAmount, float $maxBidAmount): void
    {
        $percentage = ($reservedAmount / $maxBidAmount) * 100;
        $message = sprintf(
            "%.2f%% ($%.2f) of your maximum bid amount ($%.2f) has been reserved for bids.",
            $percentage,
            $reservedAmount,
            $maxBidAmount
        );

        $notification = new Notification();
        $notification->setUser($user);
        $notification->setMessage($message);
        $notification->setType('bid_alert');

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function markNotificationAsRead(string $uuid, User $user): Notification
    {
        $notification = $this->notificationRepository->findOneBy(['uuid' => $uuid, 'user' => $user]);

        if (! $notification) {
            throw new NotFoundHttpException('Notification not found');
        }

        $notification->setIsRead(true);
        $this->entityManager->flush();

        return $notification;
    }

    public function markAllNotificationsAsRead(User $user): void
    {
        $unreadNotifications = $this->notificationRepository->findBy([
            'user' => $user,
            'isRead' => false
        ]);

        foreach ($unreadNotifications as $notification) {
            $notification->setIsRead(true);
        }

        $this->entityManager->flush();
    }

    public function hasUnreadNotifications(User $user): bool
    {
        $count = $this->notificationRepository->count([
            'user' => $user,
            'isRead' => false
        ]);

        return $count > 0;
    }
}
