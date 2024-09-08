<?php

namespace App\Controller;

use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use App\Entity\BidConfig;
use App\Form\BidConfigType;
use App\Repository\BidConfigRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;

#[Route('/api/bid-configs')]
#[OA\Tag(name: 'Bid Configurations')]
class BidConfigController extends BaseApiController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BidConfigRepository $bidConfigRepository
    ) {
    }

    #[Route('', name: 'bid_config_index', methods: ['GET'])]
    #[OA\Get(
        path: '/api/bid-configs',
        summary: 'Get all bid configurations',
        description: 'Retrieves a list of all bid configurations'
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: BidConfig::class, groups: ['bid_config:read', 'user:read', 'base:read']))
        )
    )]
    #[Security(name: 'Bearer')]
    public function index(BidConfigRepository $bidConfigRepository): JsonResponse
    {
        $bidConfigs = $bidConfigRepository->findAll();

        return $this->serializeResponse($bidConfigs, ['bid_config:read', 'user:read', 'base:read']);
    }

    #[Route('/me', name: 'bid_config_me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/bid-configs/me',
        summary: "Get current user's bid configuration",
        description: "Retrieves the bid configuration for the authenticated user"
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(ref: new Model(type: BidConfig::class, groups: ['bid_config:read', 'user:read', 'base:read']))
    )]
    #[OA\Response(
        response: 401,
        description: 'User is not authenticated',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function getUserConfig(Request $request, BidConfigRepository $bidConfigRepository): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            throw new AccessDeniedException('User is not authenticated');
        }

        $config = $bidConfigRepository->findOneBy(['user' => $user]);

        return $this->serializeResponse($config, ['bid_config:read', 'user:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('/{uuid}', name: 'bid_config_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/bid-configs/{uuid}',
        summary: 'Get a specific bid configuration',
        description: 'Retrieves details of a specific bid configuration by UUID'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the bid configuration',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(ref: new Model(type: BidConfig::class, groups: ['bid_config:read', 'user:read', 'base:read']))
    )]
    #[OA\Response(
        response: 404,
        description: 'Bid configuration not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function show(string $uuid): JsonResponse
    {
        $bidConfig = $this->bidConfigRepository->findOneBy(['uuid' => $uuid]);

        if (! $bidConfig) {
            return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse($bidConfig, ['bid_config:read', 'user:read', 'base:read']);
    }

    #[Route('', name: 'bid_config_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/bid-configs',
        summary: 'Create a new bid configuration',
        description: 'Creates a new bid configuration with the provided data'
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: BidConfigType::class))
    )]
    #[OA\Response(
        response: 201,
        description: 'Bid configuration created successfully',
        content: new OA\JsonContent(ref: new Model(type: BidConfig::class, groups: ['bid_config:read', 'user:read', 'base:read']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'errors', type: 'object')])
    )]
    #[Security(name: 'Bearer')]
    public function create(Request $request): JsonResponse
    {
        $bidConfig = new BidConfig();
        $form = $this->createForm(BidConfigType::class, $bidConfig);
        $form->submit(json_decode($request->getContent(), true));

        if ($form->isValid()) {
            $this->entityManager->persist($bidConfig);
            $this->entityManager->flush();

            return $this->serializeResponse($bidConfig, ['bid_config:read', 'user:read', 'base:read'], Response::HTTP_CREATED);
        }

        $errors = $this->getFormErrors($form);
        return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{uuid}', name: 'bid_config_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/bid-configs/{uuid}',
        summary: 'Update an existing bid configuration',
        description: 'Updates an existing bid configuration with the provided data'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the bid configuration to update',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: BidConfigType::class))
    )]
    #[OA\Response(
        response: 200,
        description: 'Bid configuration updated successfully',
        content: new OA\JsonContent(ref: new Model(type: BidConfig::class, groups: ['bid_config:read', 'user:read', 'base:read']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'errors', type: 'object')])
    )]
    #[OA\Response(
        response: 404,
        description: 'Bid configuration not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function update(Request $request, string $uuid): JsonResponse
    {
        $bidConfig = $this->bidConfigRepository->findOneBy(['uuid' => $uuid]);

        if (! $bidConfig) {
            return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(BidConfigType::class, $bidConfig);
        $form->submit(json_decode($request->getContent(), true));

        if ($form->isValid()) {
            $bidConfig->setAlertSent(false);
            $this->entityManager->flush();

            return $this->serializeResponse($bidConfig, ['bid_config:read', 'user:read', 'base:read'], Response::HTTP_CREATED);
        }

        $errors = $this->getFormErrors($form);
        return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{uuid}', name: 'bid_config_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/bid-configs/{uuid}',
        summary: 'Delete a bid configuration',
        description: 'Deletes a bid configuration by UUID'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the bid configuration to delete',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\Response(
        response: 204,
        description: 'Bid configuration deleted successfully'
    )]
    #[OA\Response(
        response: 404,
        description: 'Bid configuration not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function delete(string $uuid): JsonResponse
    {
        $bidConfig = $this->bidConfigRepository->findOneBy(['uuid' => $uuid]);

        if (! $bidConfig) {
            return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($bidConfig);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
