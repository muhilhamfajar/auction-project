<?php

namespace App\Controller;

use App\Entity\ItemMedia;
use App\Form\ItemMediaType;
use App\Repository\ItemMediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

#[Route('/api/item-medias')]
class ItemMediaController extends BaseApiController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ItemMediaRepository $itemMediaRepository,
        private UploaderHelper $uploaderHelper
    ) {
    }

    #[Route('', name: 'item_media_create', methods: ['POST'])]
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
    public function getBaseUrl(): JsonResponse
    {
        $baseUrl = $this->getParameter('app.base_url');
        $uriPrefix = $this->getParameter('vich_uploader.mappings')['item_pictures']['uri_prefix'];

        return new JsonResponse([
            'baseUrl' => $baseUrl . $uriPrefix
        ]);
    }

    #[Route('/{uuid}', name: 'item_media_update', methods: ['POST'])]
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
    public function show(string $uuid): JsonResponse
    {
        $itemMedia = $this->itemMediaRepository->findOneBy(['uuid' => $uuid]);

        if (! $itemMedia) {
            return new JsonResponse(['error' => 'ItemMedia not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse($itemMedia, ['item_media:read', 'item:read', 'base:read']);
    }

    #[Route('/{uuid}', name: 'item_media_delete', methods: ['DELETE'])]
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
