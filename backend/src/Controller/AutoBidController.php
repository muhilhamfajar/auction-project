<?php

namespace App\Controller;

use App\Entity\AutoBid;
use App\Form\AutoBidType;
use App\Repository\AutoBidRepository;
use App\Service\AsyncAutoBidService;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;

#[Route('/api/auto-bids')]
#[OA\Tag(name: 'Auto Bids')]
class AutoBidController extends BaseApiController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AutoBidRepository $autoBidRepository,
        private PaginationService $paginationService,
        private AsyncAutoBidService $asyncAutoBidService
    ) {
    }

    #[Route('', name: 'auto_bid_index', methods: ['GET'])]
    #[OA\Get(
        path: '/api/auto-bids',
        summary: 'Get a list of auto bids',
        description: 'Retrieves a paginated list of all auto bids'
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
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: AutoBid::class, groups: ['auto_bid:read', 'base:read']))),
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
        $paginatedResults = $this->paginationService->paginate($this->autoBidRepository, $request);
        return $this->serializeResponse($paginatedResults, ['auto_bid:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('/{uuid}', name: 'auto_bid_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/auto-bids/{uuid}',
        summary: 'Get a specific auto bid',
        description: 'Retrieves details of a specific auto bid by UUID'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the item',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(ref: new Model(type: AutoBid::class, groups: ['auto_bid:read', 'base:read']))
    )]
    #[OA\Response(
        response: 404,
        description: 'Auto Bid not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function show(string $uuid): JsonResponse
    {
        $autoBid = $this->autoBidRepository->findOneBy(['uuid' => $uuid]);

        if (! $autoBid) {
            return new JsonResponse(['error' => 'Auto bid not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse($autoBid, ['auto_bid:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('', name: 'auto_bid_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/auto-bids',
        summary: 'Create a new auto bid',
        description: 'Creates a new auto bid with the provided data'
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: AutoBidType::class))
    )]
    #[OA\Response(
        response: 201,
        description: 'Auto bid created successfully',
        content: new OA\JsonContent(ref: new Model(type: AutoBid::class, groups: ['auto_bid:read', 'base:read']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'errors', type: 'object')])
    )]
    #[Security(name: 'Bearer')]
    public function create(Request $request): JsonResponse
    {
        $autoBid = new AutoBid();
        $form = $this->createForm(AutoBidType::class, $autoBid);
        $data = json_decode($request->getContent(), true);

        $form->submit($data);

        if ($form->isValid()) {
            $this->entityManager->persist($autoBid);
            $this->entityManager->flush();

            try {
                $this->asyncAutoBidService->activateAutoBid($autoBid->getItem(), $autoBid->getUser());
            } catch (BadRequestHttpException $e) {
                $this->entityManager->remove($autoBid);
                $this->entityManager->flush();

                return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            return $this->serializeResponse($autoBid, ['auto_bid:read', 'base:read'], Response::HTTP_CREATED);
        }

        $errors = $this->getFormErrors($form);
        return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{uuid}', name: 'auto_bid_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/auto-bids/{uuid}',
        summary: 'Update an existing auto bid',
        description: 'Updates an existing auto bid with the provided data'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the auto bid to update',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: AutoBidType::class))
    )]
    #[OA\Response(
        response: 200,
        description: 'Auto bid updated successfully',
        content: new OA\JsonContent(ref: new Model(type: AutoBid::class, groups: ['auto_bid:read', 'base:read']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'errors', type: 'object')])
    )]
    #[OA\Response(
        response: 404,
        description: 'Auto bid not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function update(Request $request, string $uuid): JsonResponse
    {
        $autoBid = $this->autoBidRepository->findOneBy(['uuid' => $uuid]);

        if (! $autoBid) {
            return new JsonResponse(['error' => 'Auto bid not found'], Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(AutoBidType::class, $autoBid);
        $data = json_decode($request->getContent(), true);

        $form->submit($data);

        if ($form->isValid()) {
            $this->entityManager->flush();

            $this->asyncAutoBidService->activateAutoBid($autoBid->getItem(), $autoBid->getUser());

            return $this->serializeResponse($autoBid, ['auto_bid:read', 'base:read'], Response::HTTP_OK);
        }

        $errors = $this->getFormErrors($form);
        return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{uuid}', name: 'auto_bid_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/auto-bids/{uuid}',
        summary: 'Delete an auto bid',
        description: 'Deletes an auto bid by UUID'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the auto bid to delete',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\Response(
        response: 204,
        description: 'Auto bid deleted successfully'
    )]
    #[OA\Response(
        response: 404,
        description: 'Auto bid not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function delete(string $uuid): JsonResponse
    {
        $autoBid = $this->autoBidRepository->findOneBy(['uuid' => $uuid]);

        if (! $autoBid) {
            return new JsonResponse(['error' => 'Auto bid not found'], Response::HTTP_NOT_FOUND);
        }

        $this->asyncAutoBidService->deactivateAutoBid($autoBid->getItem(), $autoBid->getUser());

        $this->entityManager->remove($autoBid);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
