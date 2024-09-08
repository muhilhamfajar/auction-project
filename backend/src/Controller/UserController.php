<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
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
#[OA\Tag(name: 'Users')]
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
    #[OA\Get(
        path: '/api/users',
        summary: 'Get a list of users',
        description: 'Retrieves a paginated list of all users'
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
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: User::class, groups: ['user:read', 'base:read']))),
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
        $paginatedResults = $this->paginationService->paginate($this->userRepository, $request);
        return $this->serializeResponse($paginatedResults, ['user:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('', name: 'user_new', methods: ['POST'])]
    #[OA\Post(
        path: '/api/users',
        summary: 'Create a new user',
        description: 'Creates a new user with the provided data'
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: UserType::class))
    )]
    #[OA\Response(
        response: 201,
        description: 'User created successfully',
        content: new OA\JsonContent(ref: new Model(type: User::class, groups: ['user:read', 'base:read']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'errors', type: 'object')])
    )]
    #[Security(name: 'Bearer')]
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
    #[OA\Get(
        path: '/api/users/me',
        summary: 'Get current user',
        description: 'Retrieves the details of the currently authenticated user'
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(ref: new Model(type: User::class, groups: ['user:read', 'base:read']))
    )]
    #[OA\Response(
        response: 401,
        description: 'User not authenticated',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (! $user) {
            return new JsonResponse(['error' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->serializeResponse($user, ['user:read', 'user:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('/{uuid}', name: 'user_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/{uuid}',
        summary: 'Get a specific user',
        description: 'Retrieves details of a specific user by UUID'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the user',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(ref: new Model(type: User::class, groups: ['user:read', 'base:read']))
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
    public function show(string $uuid, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->findOneBy(['uuid' => $uuid]);
        if (! $user) {
            throw new NotFoundHttpException('User not found');
        }

        return $this->serializeResponse($user, ['user:read', 'base:read'], Response::HTTP_OK);
    }

    #[Route('/{uuid}', name: 'user_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/users/{uuid}',
        summary: 'Update an existing user',
        description: 'Updates an existing user with the provided data'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the user to update',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: UserType::class))
    )]
    #[OA\Response(
        response: 200,
        description: 'User updated successfully',
        content: new OA\JsonContent(ref: new Model(type: User::class, groups: ['user:read', 'base:read']))
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'errors', type: 'object')])
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
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
    #[OA\Delete(
        path: '/api/users/{uuid}',
        summary: 'Delete a user',
        description: 'Deletes a user by UUID'
    )]
    #[OA\Parameter(
        name: 'uuid',
        in: 'path',
        description: 'UUID of the user to delete',
        schema: new OA\Schema(type: 'string'),
        required: true
    )]
    #[OA\Response(
        response: 204,
        description: 'User deleted successfully'
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(type: 'object', properties: [new OA\Property(property: 'error', type: 'string')])
    )]
    #[Security(name: 'Bearer')]
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
