<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/users')]
class UserController extends BaseApiController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
        private PaginationService $paginationService
    ) {
    }

    #[Route('', name: 'user_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $paginatedResults = $this->paginationService->paginate($this->userRepository, $request);
        return $this->serializeResponse($paginatedResults, ['user:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('', name: 'user_new', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->submit(json_decode($request->getContent(), true));

        if ($form->isValid()) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->serializeResponse($user, ['user:read', 'user:read'], Response::HTTP_CREATED);
        }

        $errors = $this->getFormErrors($form);
        return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    #[Route('/me', name: 'api_users_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (! $user) {
            return new JsonResponse(['error' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->serializeResponse($user, ['user:read', 'user:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('/{uuid}', name: 'user_show', methods: ['GET'])]
    public function show(string $uuid, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->findOneBy(['uuid' => $uuid]);
        if (! $user) {
            throw new NotFoundHttpException('User not found');
        }

        return $this->serializeResponse($user, ['user:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('/{uuid}', name: 'user_update', methods: ['PUT'])]
    public function update(Request $request, string $uuid): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['uuid' => $uuid]);

        if (! $user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(UserType::class, $user);
        $form->submit(json_decode($request->getContent(), true));

        if ($form->isValid()) {
            $this->entityManager->flush();

            return $this->serializeResponse($user, ['user:read', 'base:read'], Response::HTTP_OK);
        }

        $errors = $this->getFormErrors($form);
        return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{uuid}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(string $uuid, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->findOneBy(['uuid' => $uuid]);
        if (! $user) {
            throw new NotFoundHttpException('User not found');
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
