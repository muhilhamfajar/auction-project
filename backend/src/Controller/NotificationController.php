<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use App\Service\PaginationService;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/api/notifications')]
#[OA\Tag(name: 'Notifications')]
class NotificationController extends BaseApiController
{
    public function __construct(
        private NotificationService $notificationService,
        private PaginationService $paginationService,
        private NotificationRepository $notificationRepository
    ) {
    }

    #[Route('', name: 'get_notifications', methods: ['GET'])]
    #[OA\Get(
        path: '/api/notifications',
        summary: 'Get user notifications',
        description: 'Retrieves a paginated list of notifications for the authenticated user'
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'The page number',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Number of items per page',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: Notification::class, groups: ['notification:read', 'base:read']))),
                new OA\Property(property: 'totalItems', type: 'integer'),
                new OA\Property(property: 'itemsPerPage', type: 'integer'),
                new OA\Property(property: 'totalPages', type: 'integer'),
                new OA\Property(property: 'currentPage', type: 'integer')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'User not authenticated',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
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
    #[OA\Post(
        path: '/api/notifications/{uuid}/mark-as-read',
        summary: 'Mark a notification as read',
        description: 'Marks a specific notification as read for the authenticated user'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the notification',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\Response(
        response: 200,
        description: 'Notification marked as read successfully',
        content: new OA\JsonContent(ref: new Model(type: Notification::class, groups: ['notification:read', 'base:read']))
    )]
    #[OA\Response(
        response: 404,
        description: 'Notification not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[OA\Response(
        response: 401,
        description: 'User not authenticated',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
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
    #[OA\Post(
        path: '/api/notifications/mark-all-read',
        summary: 'Mark all notifications as read',
        description: 'Marks all notifications as read for the authenticated user'
    )]
    #[OA\Response(
        response: 200,
        description: 'All notifications marked as read successfully',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'message', type: 'string')])
    )]
    #[OA\Response(
        response: 401,
        description: 'User not authenticated',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
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
    #[OA\Get(
        path: '/api/notifications/unread-check',
        summary: 'Check for unread notifications',
        description: 'Checks if the authenticated user has any unread notifications'
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'hasUnreadNotifications', type: 'boolean')])
    )]
    #[OA\Response(
        response: 401,
        description: 'User not authenticated',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
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
