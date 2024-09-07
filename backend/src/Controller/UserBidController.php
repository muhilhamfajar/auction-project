<?php
namespace App\Controller;

use App\Repository\BidRepository;
use App\Service\PaginationService;
use App\Service\UserBidService;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user/bids')]
class UserBidController extends BaseApiController
{
    public function __construct(private UserBidService $userBidService) {}

    #[Route('/current', name: 'user_current_bids', methods: ['GET'])]
    public function currentBids(Request $request): JsonResponse
    {
        $results = $this->userBidService->getCurrentBids($request, $this->getUser());
        return $this->json($results);
    }

    #[Route('/history', name: 'user_bid_history', methods: ['GET'])]
    public function bidHistory(Request $request, PaginationService $paginationService, BidRepository $bidRepository): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $paginatedResults = $paginationService->paginate($bidRepository, $request, ['bidder' => $user->getId()]);
        return $this->serializeResponse($paginatedResults, ['bid:read', 'base:read', 'item:read'], Response::HTTP_OK);
    }

    #[Route('/awarded', name: 'user_awarded_items', methods: ['GET'])]
    public function awardedItems(Request $request): JsonResponse
    {
        $results = $this->userBidService->getAwardedItems($request, $this->getUser());
        return $this->json($results);
    }
}
