<?php

declare(strict_types=1);

namespace App\Controller\Api\Student;

use App\Entity\Course;
use App\Entity\StudentProgress;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\StudentProgressRepository;
use App\Security\Voter\CourseVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/student', name: 'api_student_')]
#[IsGranted('ROLE_STUDENT')]
class StudentCourseController extends AbstractController
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly StudentProgressRepository $progressRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * GET /api/student/courses/{id} → détail cours + progression
     */
    #[Route('/courses/{id}', name: 'course_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $course = $this->courseRepository->find($id);
        if (!$course instanceof Course) {
            return $this->json(['message' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);

        $progress = $this->progressRepository->findOneBy(['student' => $user, 'course' => $course]);
        if (!$progress instanceof StudentProgress) {
            $progress = new StudentProgress();
            $progress->setStudent($user);
            $progress->setCourse($course);
            $progress->setLastAccessedAt(new \DateTimeImmutable());
            $this->entityManager->persist($progress);
            $this->entityManager->flush();
        } else {
            $progress->setLastAccessedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
        }

        return $this->json([
            'course' => [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'content' => $course->getContent(),
                'sort_order' => $course->getSortOrder(),
                'is_published' => $course->isPublished(),
            ],
            'progress' => [
                'completed_lessons' => $progress->getCompletedLessons(),
                'last_accessed_at' => $progress->getLastAccessedAt()?->format(\DateTimeInterface::ATOM),
                'progress_percentage' => $progress->getProgressPercentage(),
            ],
        ]);
    }

    /**
     * POST /api/student/courses/{id}/complete → marquer progression
     */
    #[Route('/courses/{id}/complete', name: 'course_complete', methods: ['POST'])]
    public function complete(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $course = $this->courseRepository->find($id);
        if (!$course instanceof Course) {
            return $this->json(['message' => 'Course not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);

        $progress = $this->progressRepository->findOneBy(['student' => $user, 'course' => $course]);
        if (!$progress instanceof StudentProgress) {
            $progress = new StudentProgress();
            $progress->setStudent($user);
            $progress->setCourse($course);
            $this->entityManager->persist($progress);
        }

        $data = json_decode((string) $request->getContent(), true);
        $lessonId = isset($data['lesson_id']) ? $data['lesson_id'] : null;
        $percentage = isset($data['progress_percentage']) ? (float) $data['progress_percentage'] : null;

        if ($lessonId !== null) {
            $completed = $progress->getCompletedLessons();
            if (!\in_array($lessonId, $completed, true)) {
                $completed[] = $lessonId;
                $progress->setCompletedLessons($completed);
            }
        }
        if ($percentage !== null && $percentage >= 0 && $percentage <= 100) {
            $progress->setProgressPercentage($percentage);
        }
        $progress->setLastAccessedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $this->json([
            'progress' => [
                'completed_lessons' => $progress->getCompletedLessons(),
                'progress_percentage' => $progress->getProgressPercentage(),
            ],
        ]);
    }

    /**
     * GET /api/student/progress → progression globale
     */
    #[Route('/progress', name: 'progress', methods: ['GET'])]
    public function progress(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $all = $this->progressRepository->findBy(['student' => $user], ['lastAccessedAt' => 'DESC']);

        return $this->json([
            'progress' => array_map(
                fn (StudentProgress $p) => [
                    'course_id' => $p->getCourse()?->getId(),
                    'course_title' => $p->getCourse()?->getTitle(),
                    'completed_lessons' => $p->getCompletedLessons(),
                    'progress_percentage' => $p->getProgressPercentage(),
                    'last_accessed_at' => $p->getLastAccessedAt()?->format(\DateTimeInterface::ATOM),
                ],
                $all
            ),
        ]);
    }
}
