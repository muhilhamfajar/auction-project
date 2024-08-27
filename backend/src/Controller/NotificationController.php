<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use App\Service\PaginationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/api/notifications')]
class NotificationController extends BaseApiController
{
    public function __construct(
        private NotificationService $notificationService,
        private PaginationService $paginationService,
        private NotificationRepository $notificationRepository
    ) {
    }

    #[Route('', name: 'get_notifications', methods: ['GET'])]
    public function getNotifications(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $paginatedResults = $this->paginationService->paginate($this->notificationRepository, $request, ['user' => $user->getId()]);
        return $this->serializeResponse($paginatedResults, ['notification:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('/{uuid}/mark-as-read', name: 'mark_notification_as_read', methods: ['POST'])]
    public function markAsRead(string $uuid): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $notification = $this->notificationService->markNotificationAsRead($uuid, $user);
            return $this->serializeResponse($notification, ['notification:read', 'base:read'], Response::HTTP_OK);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/mark-all-read', name: 'mark_all_notifications_as_read', methods: ['POST'])]
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $this->notificationService->markAllNotificationsAsRead($user);

        return $this->json(['message' => 'All notifications marked as read'], Response::HTTP_OK);
    }

    #[Route('/unread-check', name: 'check_unread_notifications', methods: ['GET'])]
    public function checkUnreadNotifications(): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $hasUnread = $this->notificationService->hasUnreadNotifications($user);

        return $this->json(['hasUnreadNotifications' => $hasUnread], Response::HTTP_OK);
    }
}
