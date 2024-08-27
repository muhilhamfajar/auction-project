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

#[Route('/api/items')]
class ItemController extends BaseApiController
{
    public function __construct(
        private ItemService $itemService,
        private ItemRepository $itemRepository
    ) {
    }

    #[Route('', name: 'item_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $paginatedResults = $this->itemService->getPaginatedItems($request);
        return $this->serializeResponse($paginatedResults, ['item:read', 'bid:read', 'item_media:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('/{uuid}', name: 'item_show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        $data = $this->itemService->getItemWithHighestBid($uuid);

        if (! $data) {
            return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse($data, ['item:read', 'bid:read', 'item_media:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('', name: 'item_create', methods: ['POST'])]
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
