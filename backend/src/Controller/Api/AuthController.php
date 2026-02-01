<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Inscription : { email, password, firstName, lastName, role } → { user, token }
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['message' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $firstName = $data['firstName'] ?? '';
        $lastName = $data['lastName'] ?? '';
        $role = $data['role'] ?? 'ROLE_STUDENT';

        if ($this->userRepository->findOneBy(['email' => $email])) {
            return $this->json(['message' => 'Un compte existe déjà avec cet email.'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles([$role === 'ROLE_TEACHER' ? 'ROLE_TEACHER' : 'ROLE_STUDENT']);
        $user->setIsVerified(false);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }
            return $this->json(['message' => 'Validation failed', 'errors' => $messages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->jwtManager->create($user);

        return $this->json([
            'user' => $this->serializeUser($user),
            'token' => $token,
        ], Response::HTTP_CREATED);
    }

    /**
     * Connexion : géré par Security (json_login) → Lexik retourne { token }.
     * Cette route existe pour que le Router matche ; le listener Security répond avant.
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->json(['message' => 'Use JSON body with email and password.'], Response::HTTP_OK);
    }

    /**
     * Utilisateur connecté (alias sans conflit API Platform) : GET /api/auth/me → { user }
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        return $this->json(['user' => $this->serializeUser($user)]);
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'roles' => $user->getRoles(),
            'is_verified' => $user->isVerified(),
        ];
    }
}
