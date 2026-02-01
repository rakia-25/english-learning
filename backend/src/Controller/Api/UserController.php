<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users', name: 'api_users_')]
class UserController extends AbstractController
{
    /**
     * Utilisateur connecté : GET /api/users/me → { user }
     * Priorité élevée pour être pris avant la route API Platform /api/users/{id}.
     */
    #[Route('/me', name: 'me', methods: ['GET'], priority: 10)]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'roles' => $user->getRoles(),
                'is_verified' => $user->isVerified(),
            ],
        ]);
    }
}
