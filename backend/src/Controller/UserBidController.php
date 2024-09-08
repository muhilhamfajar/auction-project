<?php

namespace App\Controller;

use App\Entity\Bid;
use App\Service\UserBidService;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user/bids')]
#[OA\Tag(name: 'User Bids')]
class UserBidController extends BaseApiController
{
    public function __construct(private UserBidService $userBidService)
    {
    }

    #[Route('/current', name: 'user_current_bids', methods: ['GET'])]
    #[OA\Get(
        path: '/api/user/bids/current',
        summary: 'Get current user\'s active bids',
        description: 'Retrieves a list of active bids for the authenticated user'
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
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: Bid::class, groups: ['bid:read', 'base:read', 'item:read']))),
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
    public function currentBids(Request $request): JsonResponse
    {
        $results = $this->userBidService->getCurrentBids($request, $this->getUser());
        return $this->json($results);
    }

    #[Route('/awarded', name: 'user_awarded_items', methods: ['GET'])]
    #[OA\Get(
        path: '/api/user/bids/awarded',
        summary: 'Get user\'s awarded items',
        description: 'Retrieves a list of items awarded to the authenticated user'
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
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: Bid::class, groups: ['bid:read', 'base:read', 'item:read']))),
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
    public function awardedItems(Request $request): JsonResponse
    {
        $results = $this->userBidService->getAwardedItems($request, $this->getUser());
        return $this->json($results);
    }
}
