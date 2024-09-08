<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Firebase\JWT\JWT;

#[OA\Tag(name: 'Mercure')]
class MercureController extends AbstractController
{
    #[Route('/api/mercure-jwt', name: 'mercure_jwt', methods: ['GET'])]
    #[OA\Get(
        path: '/api/mercure-jwt',
        summary: 'Get Mercure JWT token',
        description: 'Retrieves a JWT token for Mercure real-time updates'
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'token', type: 'string', description: 'JWT token for Mercure')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'User not authenticated',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string')
            ]
        )
    )]
    #[Security(name: 'Bearer')]
    public function getMercureJwt(): JsonResponse
    {
        $user = $this->getUser();
        if (! $user) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $secret = $this->getParameter('mercure.jwt_secret');

        $payload = [
            'mercure' => [
                'subscribe' => ['*'],
                'publish' => ['*']
            ]
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        return $this->json(['token' => $token]);
    }
}
