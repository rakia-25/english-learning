<?php

declare(strict_types=1);

namespace App\Controller\Api\Teacher;

use App\Entity\Classroom;
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

#[Route('/api/teacher/classrooms', name: 'api_teacher_classrooms_')]
#[IsGranted('ROLE_TEACHER')]
class TeacherClassroomController extends AbstractController
{
    public function __construct(
        private readonly ClassroomRepository $classroomRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * GET /api/teacher/classrooms → liste mes classes
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $classrooms = $this->classroomRepository->findByTeacher($user);

        return $this->json([
            'classrooms' => array_map(
                fn (Classroom $c) => $this->serializeClassroomSummary($c),
                $classrooms
            ),
        ]);
    }

    /**
     * POST /api/teacher/classrooms : { name, description } → crée classe avec code auto
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode((string) $request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['message' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($data['name'] ?? ''));
        $description = isset($data['description']) ? trim((string) $data['description']) : null;

        if ($name === '') {
            return $this->json(['message' => 'name is required'], Response::HTTP_BAD_REQUEST);
        }

        $classroom = new Classroom();
        $classroom->setName($name);
        $classroom->setDescription($description ?: null);
        $classroom->setTeacher($user);
        $classroom->setIsActive(true);
        $classroom->generateCode();

        $this->entityManager->persist($classroom);
        $this->entityManager->flush();

        return $this->json($this->serializeClassroom($classroom), Response::HTTP_CREATED);
    }

    /**
     * GET /api/teacher/classrooms/{id} → détail avec students et courses
     */
    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $classroom = $this->classroomRepository->find($id);
        if (!$classroom instanceof Classroom) {
            return $this->json(['message' => 'Classroom not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ClassroomVoter::VIEW, $classroom);

        return $this->json($this->serializeClassroomDetail($classroom));
    }

    /**
     * PATCH /api/teacher/classrooms/{id}
     */
    #[Route('/{id}', name: 'update', methods: ['PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $classroom = $this->classroomRepository->find($id);
        if (!$classroom instanceof Classroom) {
            return $this->json(['message' => 'Classroom not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ClassroomVoter::EDIT, $classroom);

        $data = json_decode((string) $request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['message' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name']) && trim((string) $data['name']) !== '') {
            $classroom->setName(trim((string) $data['name']));
        }
        if (array_key_exists('description', $data)) {
            $classroom->setDescription($data['description'] === null ? null : trim((string) $data['description']));
        }
        if (isset($data['isActive'])) {
            $classroom->setIsActive((bool) $data['isActive']);
        }

        $this->entityManager->flush();

        return $this->json($this->serializeClassroom($classroom));
    }

    /**
     * DELETE /api/teacher/classrooms/{id}
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $classroom = $this->classroomRepository->find($id);
        if (!$classroom instanceof Classroom) {
            return $this->json(['message' => 'Classroom not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ClassroomVoter::DELETE, $classroom);

        $this->entityManager->remove($classroom);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serializeClassroomSummary(Classroom $c): array
    {
        return [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'description' => $c->getDescription(),
            'code' => $c->getCode(),
            'is_active' => $c->isActive(),
            'created_at' => $c->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    private function serializeClassroom(Classroom $c): array
    {
        return [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'description' => $c->getDescription(),
            'code' => $c->getCode(),
            'is_active' => $c->isActive(),
            'created_at' => $c->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    private function serializeClassroomDetail(Classroom $c): array
    {
        $students = [];
        foreach ($c->getStudents() as $student) {
            $students[] = [
                'id' => $student->getId(),
                'email' => $student->getEmail(),
                'first_name' => $student->getFirstName(),
                'last_name' => $student->getLastName(),
            ];
        }
        $courses = [];
        foreach ($c->getCourses() as $course) {
            $courses[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'sort_order' => $course->getSortOrder(),
                'is_published' => $course->isPublished(),
            ];
        }
        return array_merge($this->serializeClassroom($c), [
            'students' => $students,
            'courses' => $courses,
        ]);
    }
}
