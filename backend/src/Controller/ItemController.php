<?php

namespace App\Controller;

use App\Entity\Item;
use App\Form\ItemType;
use App\Repository\ItemRepository;
use App\Service\ItemService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;

#[Route('/api/items')]
#[OA\Tag(name: 'Items')]
class ItemController extends BaseApiController
{
    public function __construct(
        private ItemService $itemService,
        private ItemRepository $itemRepository
    ) {
    }

    #[Route('', name: 'item_index', methods: ['GET'])]
    #[OA\Get(
        path: '/api/items',
        summary: 'Get a list of items',
        description: 'Retrieves a paginated list of all items'
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
    #[OA\Parameter(
        name: 'sort',
        in: 'query',
        description: 'Sort field',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'order',
        in: 'query',
        description: 'Sort order (ASC or DESC)',
        schema: new OA\Schema(type: 'string', enum: ['ASC', 'DESC'])
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: Item::class, groups: ['item:read', 'bid:read', 'item_media:read', 'base:read']))),
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
        $paginatedResults = $this->itemService->getPaginatedItems($request);
        return $this->serializeResponse($paginatedResults, ['item:read', 'bid:read', 'item_media:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('/{uuid}', name: 'item_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/items/{uuid}',
        summary: 'Get a specific item',
        description: 'Retrieves details of a specific item by UUID'
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
        content: new OA\JsonContent(ref: new Model(type: Item::class, groups: ['item:read', 'bid:read', 'item_media:read', 'base:read']))
    )]
    #[OA\Response(
        response: 404,
        description: 'Item not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function show(string $uuid): JsonResponse
    {
        $data = $this->itemService->getItemWithHighestBid($uuid);

        if (! $data) {
            return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse($data, ['item:read', 'bid:read', 'item_media:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('', name: 'item_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/items',
        summary: 'Create a new item',
        description: 'Creates a new item with the provided data'
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: ItemType::class))
    )]
    #[OA\Response(
        response: 201,
        description: 'Item created successfully',
        content: new OA\JsonContent(ref: new Model(type: Item::class, groups: ['item:read', 'base:read']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'errors', type: 'object')])
    )]
    #[Security(name: 'Bearer')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $form = $this->createForm(ItemType::class);
        $form->submit($data);

        if ($form->isValid()) {
            $item = $this->itemService->createItem($data);
            return $this->serializeResponse($item, ['item:read', 'base:read'], Response::HTTP_CREATED);
        }

        $errors = $this->getFormErrors($form);
        return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{uuid}', name: 'item_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/items/{uuid}',
        summary: 'Update an existing item',
        description: 'Updates an existing item with the provided data'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the item to update',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: ItemType::class))
    )]
    #[OA\Response(
        response: 200,
        description: 'Item updated successfully',
        content: new OA\JsonContent(ref: new Model(type: Item::class, groups: ['item:read', 'base:read']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'errors', type: 'object')])
    )]
    #[OA\Response(
        response: 404,
        description: 'Item not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function update(Request $request, string $uuid): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $item = $this->itemService->getItemWithHighestBid($uuid)['item'] ?? null;

        if (! $item) {
            return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(ItemType::class, $item);
        $form->submit($data);

        if ($form->isValid()) {
            $updatedItem = $this->itemService->updateItem($item, $data);
            return $this->serializeResponse($updatedItem, ['item:read', 'base:read'], Response::HTTP_OK);
        }

        $errors = $this->getFormErrors($form);
        return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{uuid}', name: 'item_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/items/{uuid}',
        summary: 'Delete an item',
        description: 'Deletes an item by UUID'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the item to delete',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\Response(
        response: 204,
        description: 'Item deleted successfully'
    )]
    #[OA\Response(
        response: 404,
        description: 'Item not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function delete(string $uuid): JsonResponse
    {
        $item = $this->itemRepository->findOneBy(['uuid' => $uuid]);

        if (! $item) {
            return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        $this->itemService->deleteItem($item);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
