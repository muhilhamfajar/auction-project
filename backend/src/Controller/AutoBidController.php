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

#[Route('/api/auto-bids')]
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
    public function index(Request $request): JsonResponse
    {
        $paginatedResults = $this->paginationService->paginate($this->autoBidRepository, $request);
        return $this->serializeResponse($paginatedResults, ['auto_bid:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('/{uuid}', name: 'auto_bid_show', methods: ['GET'])]
    public function show(string $uuid): JsonResponse
    {
        $autoBid = $this->autoBidRepository->findOneBy(['uuid' => $uuid]);

        if (! $autoBid) {
            return new JsonResponse(['error' => 'Auto bid not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->serializeResponse($autoBid, ['auto_bid:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('', name: 'auto_bid_create', methods: ['POST'])]
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
