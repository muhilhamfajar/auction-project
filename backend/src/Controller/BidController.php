<?php

namespace App\Controller;

use App\Entity\Bid;
use App\Form\BidType;
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
    public function index(Request $request): JsonResponse
    {
        $paginatedResults = $this->paginationService->paginate($this->bidRepository, $request);
        return $this->serializeResponse($paginatedResults, ['bid:read','user:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('/{uuid}', name: 'bid_show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        $bid = $this->bidRepository->findOneBy(['uuid' => $uuid]);

        if (! $bid) {
            return new JsonResponse(['error' => 'Bid not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse($bid, ['bid:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('', name: 'bid_create', methods: ['POST'])]
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
