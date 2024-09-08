<?php

namespace App\Controller;

use App\Entity\Bid;
use App\Form\BidType;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use App\Repository\BidRepository;
use App\Service\BidService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/bids')]
#[OA\Tag(name: 'Bids')]
class BidController extends BaseApiController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BidRepository $bidRepository,
        private PaginationService $paginationService,
        private BidService $bidService
    ) {
    }

    #[Route('', name: 'bid_index', methods: ['GET'])]
    #[OA\Get(
        path: '/api/bids',
        summary: 'Get a list of bids',
        description: 'Retrieves a paginated list of all bids'
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
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: Bid::class, groups: ['bid:read', 'user:read', 'base:read', 'item:read']))),
                new OA\Property(property: 'totalItems', type: 'integer'),
                new OA\Property(property: 'itemsPerPage', type: 'integer'),
                new OA\Property(property: 'totalPages', type: 'integer'),
                new OA\Property(property: 'currentPage', type: 'integer')
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    public function index(Request $request): JsonResponse
    {
        $paginatedResults = $this->paginationService->paginate($this->bidRepository, $request);
        return $this->serializeResponse($paginatedResults, ['bid:read','user:read', 'base:read', 'item:read'], Response::HTTP_OK);
    }

    #[Route('/{uuid}', name: 'bid_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/bids/{uuid}',
        summary: 'Get a specific bid',
        description: 'Retrieves details of a specific bid by UUID'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the bid',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(ref: new Model(type: Bid::class, groups: ['bid:read', 'base:read']))
    )]
    #[OA\Response(
        response: 404,
        description: 'Bid not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function show(string $uuid): JsonResponse
    {
        $bid = $this->bidRepository->findOneBy(['uuid' => $uuid]);

        if (! $bid) {
            return new JsonResponse(['error' => 'Bid not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse($bid, ['bid:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('', name: 'bid_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/bids',
        summary: 'Create a new bid',
        description: 'Creates a new bid with the provided data'
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: BidType::class))
    )]
    #[OA\Response(
        response: 201,
        description: 'Bid created successfully',
        content: new OA\JsonContent(ref: new Model(type: Bid::class, groups: ['bid:read', 'base:read']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'errors', type: 'object')])
    )]
    #[Security(name: 'Bearer')]
    public function create(Request $request): JsonResponse
    {
        $bid = new Bid();
        $form = $this->createForm(BidType::class, $bid);
        $data = json_decode($request->getContent(), true);

        $form->submit($data);

        if ($form->isValid()) {
            $bid = $this->bidService->placeBid($bid);

            return $this->serializeResponse($bid, ['bid:read', 'base:read'], Response::HTTP_CREATED);
        }

        $errors = $this->getFormErrors($form);
        return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{uuid}', name: 'bid_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/bids/{uuid}',
        summary: 'Update an existing bid',
        description: 'Updates an existing bid with the provided data'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the bid to update',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: BidType::class))
    )]
    #[OA\Response(
        response: 200,
        description: 'Bid updated successfully',
        content: new OA\JsonContent(ref: new Model(type: Bid::class, groups: ['bid:read', 'base:read']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'errors', type: 'object')])
    )]
    #[OA\Response(
        response: 404,
        description: 'Bid not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function update(Request $request, string $uuid): JsonResponse
    {
        $bid = $this->bidRepository->findOneBy(['uuid' => $uuid]);

        if (! $bid) {
            return new JsonResponse(['error' => 'Bid not found'], Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(BidType::class, $bid);
        $data = json_decode($request->getContent(), true);

        $form->submit($data);

        if ($form->isValid()) {
            $bid = $this->bidService->placeBid($bid);

            return $this->serializeResponse($bid, ['bid:read', 'base:read'], Response::HTTP_CREATED);
        }

        $errors = $this->getFormErrors($form);
        return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{uuid}', name: 'bid_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/bids/{uuid}',
        summary: 'Delete a bid',
        description: 'Deletes a bid by UUID'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the bid to delete',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\Response(
        response: 204,
        description: 'Bid deleted successfully'
    )]
    #[OA\Response(
        response: 404,
        description: 'Bid not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function delete(string $uuid): JsonResponse
    {
        $bid = $this->bidRepository->findOneBy(['uuid' => $uuid]);

        if (! $bid) {
            return new JsonResponse(['error' => 'Bid not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($bid);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
