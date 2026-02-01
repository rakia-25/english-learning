<?php

declare(strict_types=1);

namespace App\Controller\Api\Teacher;

use App\Entity\Classroom;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\ClassroomRepository;
use App\Repository\CourseRepository;
use App\Security\Voter\ClassroomVoter;
use App\Security\Voter\CourseVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/teacher', name: 'api_teacher_')]
#[IsGranted('ROLE_TEACHER')]
class TeacherCourseController extends AbstractController
{
    public function __construct(
        private readonly ClassroomRepository $classroomRepository,
        private readonly CourseRepository $courseRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * GET /api/teacher/courses → tous les cours créés par l'enseignant (toutes classes)
     */
    #[Route('/courses', name: 'courses_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $courses = $this->courseRepository->findByTeacher($user);

        return $this->json([
            'courses' => array_map(
                fn (Course $c) => $this->serializeCourseWithClassroom($c),
                $courses
            ),
        ]);
    }

    /**
     * POST /api/teacher/classrooms/{id}/courses : { title, description, content }
     */
    #[Route('/classrooms/{id}/courses', name: 'classroom_courses_create', methods: ['POST'])]
    public function createInClassroom(string $id, Request $request): JsonResponse
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

        $title = trim((string) ($data['title'] ?? ''));
        $description = isset($data['description']) ? trim((string) $data['description']) : null;
        $content = isset($data['content']) ? trim((string) $data['content']) : null;

        if ($title === '') {
            return $this->json(['message' => 'title is required'], Response::HTTP_BAD_REQUEST);
        }

        $course = new Course();
        $course->setTitle($title);
        $course->setDescription($description ?: null);
        $course->setContent($content ?: null);
        $course->setClassroom($classroom);
        $course->setSortOrder($classroom->getCourses()->count());
        $course->setIsPublished(false);

        $this->entityManager->persist($course);
        $classroom->addCourse($course);
        $this->entityManager->flush();

        return $this->json($this->serializeCourse($course), Response::HTTP_CREATED);
    }

    /**
     * PATCH /api/teacher/courses/{id}
     */
    #[Route('/courses/{id}', name: 'course_update', methods: ['PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $course = $this->courseRepository->find($id);
        if (!$course instanceof Course) {
            return $this->json(['message' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        $data = json_decode((string) $request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['message' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['title']) && trim((string) $data['title']) !== '') {
            $course->setTitle(trim((string) $data['title']));
        }
        if (array_key_exists('description', $data)) {
            $course->setDescription($data['description'] === null ? null : trim((string) $data['description']));
        }
        if (array_key_exists('content', $data)) {
            $course->setContent($data['content'] === null ? null : trim((string) $data['content']));
        }
        if (isset($data['sort_order']) && is_numeric($data['sort_order'])) {
            $course->setSortOrder((int) $data['sort_order']);
        }
        if (isset($data['is_published'])) {
            $course->setIsPublished((bool) $data['is_published']);
        }

        $this->entityManager->flush();

        return $this->json($this->serializeCourse($course));
    }

    /**
     * DELETE /api/teacher/courses/{id}
     */
    #[Route('/courses/{id}', name: 'course_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $course = $this->courseRepository->find($id);
        if (!$course instanceof Course) {
            return $this->json(['message' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(CourseVoter::DELETE, $course);

        $this->entityManager->remove($course);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serializeCourse(Course $c): array
    {
        return [
            'id' => $c->getId(),
            'title' => $c->getTitle(),
            'description' => $c->getDescription(),
            'content' => $c->getContent(),
            'classroom_id' => $c->getClassroom()?->getId(),
            'sort_order' => $c->getSortOrder(),
            'is_published' => $c->isPublished(),
            'published_at' => $c->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $c->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    private function serializeCourseWithClassroom(Course $c): array
    {
        $classroom = $c->getClassroom();
        return array_merge($this->serializeCourse($c), [
            'classroom_name' => $classroom?->getName(),
        ]);
    }
}
