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

#[Route('/api/bid-configs')]
class BidConfigController extends BaseApiController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BidConfigRepository $bidConfigRepository
    ) {
    }

    #[Route('', name: 'bid_config_index', methods: ['GET'])]
    public function index(BidConfigRepository $bidConfigRepository): JsonResponse
    {
        $bidConfigs = $bidConfigRepository->findAll();

        return $this->serializeResponse($bidConfigs, ['bid_config:read', 'user:read', 'base:read']);
    }

    #[Route('/me', name: 'bid_config_me', methods: ['GET'])]
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
    public function show(string $uuid): JsonResponse
    {
        $bidConfig = $this->bidConfigRepository->findOneBy(['uuid' => $uuid]);

        if (! $bidConfig) {
            return new JsonResponse(['error' => 'Item not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse($bidConfig, ['bid_config:read', 'user:read', 'base:read']);
    }

    #[Route('', name: 'bid_config_create', methods: ['POST'])]
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
