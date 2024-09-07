<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Firebase\JWT\JWT;

class MercureController extends AbstractController
{
    #[Route('/api/mercure-jwt', name: 'mercure_jwt', methods: ['GET'])]
    public function getMercureJwt(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
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