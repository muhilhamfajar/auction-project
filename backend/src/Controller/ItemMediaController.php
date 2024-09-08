<?php

namespace App\Controller;

use App\Entity\ItemMedia;
use App\Form\ItemMediaType;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use App\Repository\ItemMediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[Route('/api/item-medias')]
#[OA\Tag(name: 'Item Media')]
class ItemMediaController extends BaseApiController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ItemMediaRepository $itemMediaRepository,
        private UploaderHelper $uploaderHelper
    ) {
    }

    #[Route('', name: 'item_media_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/item-medias',
        summary: 'Create a new item media',
        description: 'Creates a new item media with the provided data'
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(ref: new Model(type: ItemMediaType::class))
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Item media created successfully',
        content: new OA\JsonContent(ref: new Model(type: ItemMedia::class, groups: ['item_media:read', 'item:read', 'base:read']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'errors', type: 'object')])
    )]
    #[Security(name: 'Bearer')]
    public function create(Request $request): JsonResponse
    {
        $itemMedia = new ItemMedia();
        $form = $this->createForm(ItemMediaType::class, $itemMedia);

        $data = $request->request->all();
        $files = $request->files->all();

        $form->submit(array_merge($data, $files));

        if ($form->isValid()) {
            $this->entityManager->persist($itemMedia);
            $this->entityManager->flush();

            return $this->serializeResponse($itemMedia, ['item_media:read', 'item:read', 'base:read'], Response::HTTP_CREATED);
        }

        $errors = $this->getFormErrors($form);
        return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/base-url', name: 'item_media_base_url', methods: ['GET'])]
    #[OA\Get(
        path: '/api/item-medias/base-url',
        summary: 'Get base URL for item media',
        description: 'Retrieves the base URL for item media files'
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'baseUrl', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function getBaseUrl(): JsonResponse
    {
        $baseUrl = $this->getParameter('app.base_url');
        $uriPrefix = $this->getParameter('vich_uploader.mappings')['item_pictures']['uri_prefix'];

        return new JsonResponse([
            'baseUrl' => $baseUrl . $uriPrefix
        ]);
    }

    #[Route('/{uuid}', name: 'item_media_update', methods: ['POST'])]
    #[OA\Post(
        path: '/api/item-medias/{uuid}',
        summary: 'Update an existing item media',
        description: 'Updates an existing item media with the provided data'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the item media to update',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(ref: new Model(type: ItemMediaType::class))
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Item media updated successfully',
        content: new OA\JsonContent(ref: new Model(type: ItemMedia::class, groups: ['item_media:read', 'item:read', 'base:read']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'errors', type: 'object')])
    )]
    #[OA\Response(
        response: 404,
        description: 'Item media not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function update(Request $request, string $uuid): JsonResponse
    {
        $itemMedia = $this->itemMediaRepository->findOneBy(['uuid' => $uuid]);

        if (! $itemMedia) {
            return new JsonResponse(['error' => 'ItemMedia not found'], Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(ItemMediaType::class, $itemMedia);

        $data = $request->request->all();
        $files = $request->files->all();

        $form->submit(array_merge($data, $files), false);

        if ($form->isValid()) {
            $this->entityManager->flush();

            return $this->serializeResponse($itemMedia, ['item_media:read', 'item:read', 'base:read']);
        }

        $errors = $this->getFormErrors($form);
        return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{uuid}', name: 'item_media_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/item-medias/{uuid}',
        summary: 'Get a specific item media',
        description: 'Retrieves details of a specific item media by UUID'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the item media',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(ref: new Model(type: ItemMedia::class, groups: ['item_media:read', 'item:read', 'base:read']))
    )]
    #[OA\Response(
        response: 404,
        description: 'Item media not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function show(string $uuid): JsonResponse
    {
        $itemMedia = $this->itemMediaRepository->findOneBy(['uuid' => $uuid]);

        if (! $itemMedia) {
            return new JsonResponse(['error' => 'ItemMedia not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse($itemMedia, ['item_media:read', 'item:read', 'base:read']);
    }

    #[Route('/{uuid}', name: 'item_media_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/item-medias/{uuid}',
        summary: 'Delete an item media',
        description: 'Deletes an item media by UUID'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the item media to delete',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\Response(
        response: 204,
        description: 'Item media deleted successfully'
    )]
    #[OA\Response(
        response: 404,
        description: 'Item media not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function delete(string $uuid): JsonResponse
    {
        $itemMedia = $this->itemMediaRepository->findOneBy(['uuid' => $uuid]);

        if (! $itemMedia) {
            return new JsonResponse(['error' => 'ItemMedia not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($itemMedia);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
