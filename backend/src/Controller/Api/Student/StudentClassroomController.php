<?php

declare(strict_types=1);

namespace App\Controller\Api\Student;

use App\Entity\Classroom;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\ClassroomRepository;
use App\Security\Voter\ClassroomVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/student', name: 'api_student_')]
#[IsGranted('ROLE_STUDENT')]
class StudentClassroomController extends AbstractController
{
    public function __construct(
        private readonly ClassroomRepository $classroomRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * POST /api/student/classrooms/join : { code } → rejoint la classe
     */
    #[Route('/classrooms/join', name: 'classrooms_join', methods: ['POST'])]
    public function join(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode((string) $request->getContent(), true);
        if (!\is_array($data) || empty(trim((string) ($data['code'] ?? '')))) {
            return $this->json(['message' => 'code is required'], Response::HTTP_BAD_REQUEST);
        }

        $code = strtoupper(trim((string) $data['code']));
        $classroom = $this->classroomRepository->findOneBy(['code' => $code]);

        if (!$classroom instanceof Classroom) {
            return $this->json(['message' => 'Classe introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (!$classroom->isActive()) {
            return $this->json(['message' => 'Cette classe n\'est plus active'], Response::HTTP_BAD_REQUEST);
        }

        if ($classroom->getStudents()->contains($user)) {
            return $this->json(['message' => 'Vous êtes déjà inscrit à cette classe'], Response::HTTP_CONFLICT);
        }

        $classroom->addStudent($user);
        $this->entityManager->flush();

        return $this->json($this->serializeClassroomSummary($classroom), Response::HTTP_CREATED);
    }

    /**
     * GET /api/student/classrooms → mes classes
     */
    #[Route('/classrooms', name: 'classrooms_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $classrooms = $user->getClassrooms();

        return $this->json([
            'classrooms' => array_map(
                fn (Classroom $c) => $this->serializeClassroomSummary($c),
                $classrooms->toArray()
            ),
        ]);
    }

    /**
     * GET /api/student/classrooms/{id}/courses → cours de la classe
     */
    #[Route('/classrooms/{id}/courses', name: 'classroom_courses', methods: ['GET'])]
    public function courses(string $id): JsonResponse
    {
        $classroom = $this->classroomRepository->find($id);
        if (!$classroom instanceof Classroom) {
            return $this->json(['message' => 'Classroom not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ClassroomVoter::VIEW, $classroom);

        $courses = $classroom->getCourses();

        return $this->json([
            'courses' => array_map(
                fn (Course $c) => [
                    'id' => $c->getId(),
                    'title' => $c->getTitle(),
                    'description' => $c->getDescription(),
                    'sort_order' => $c->getSortOrder(),
                    'is_published' => $c->isPublished(),
                ],
                $courses->toArray()
            ),
        ]);
    }

    private function serializeClassroomSummary(Classroom $c): array
    {
        return [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'description' => $c->getDescription(),
            'code' => $c->getCode(),
            'is_active' => $c->isActive(),
        ];
    }
}
