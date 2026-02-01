<?php

declare(strict_types=1);

namespace App\Controller\Api\Teacher;

use App\Entity\Course;
use App\Entity\Exam;
use App\Entity\Question;
use App\Entity\StudentExamAttempt;
use App\Repository\CourseRepository;
use App\Repository\ExamRepository;
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
class TeacherExamController extends AbstractController
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
        private readonly ExamRepository $examRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * POST /api/teacher/courses/{id}/exams : { title, duration, passingScore }
     */
    #[Route('/courses/{id}/exams', name: 'course_exams_create', methods: ['POST'])]
    public function createExam(string $id, Request $request): JsonResponse
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

        $title = trim((string) ($data['title'] ?? ''));
        $duration = isset($data['duration']) ? (int) $data['duration'] : 30;
        $passingScore = isset($data['passingScore']) ? (int) $data['passingScore'] : 60;

        if ($title === '') {
            return $this->json(['message' => 'title is required'], Response::HTTP_BAD_REQUEST);
        }

        $exam = new Exam();
        $exam->setTitle($title);
        $exam->setDescription($data['description'] ?? null ? trim((string) $data['description']) : null);
        $exam->setCourse($course);
        $exam->setDuration(max(1, $duration));
        $exam->setPassingScore(max(0, min(100, $passingScore)));
        $exam->setIsPublished(false);

        $this->entityManager->persist($exam);
        $course->addExam($exam);
        $this->entityManager->flush();

        return $this->json($this->serializeExam($exam), Response::HTTP_CREATED);
    }

    /**
     * POST /api/teacher/exams/{id}/questions : { questions: [{ questionText, type, points, options? }] }
     */
    #[Route('/exams/{id}/questions', name: 'exam_questions_create', methods: ['POST'])]
    public function addQuestions(string $id, Request $request): JsonResponse
    {
        $exam = $this->examRepository->find($id);
        if (!$exam instanceof Exam) {
            return $this->json(['message' => 'Exam not found'], Response::HTTP_NOT_FOUND);
        }

        $course = $exam->getCourse();
        if ($course instanceof Course) {
            $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);
        }

        $data = json_decode((string) $request->getContent(), true);
        if (!\is_array($data) || !isset($data['questions']) || !\is_array($data['questions'])) {
            return $this->json(['message' => 'questions array is required'], Response::HTTP_BAD_REQUEST);
        }

        $sortOrder = $exam->getQuestions()->count();
        foreach ($data['questions'] as $q) {
            $questionText = trim((string) ($q['questionText'] ?? ''));
            $type = $q['type'] ?? Question::TYPE_MULTIPLE_CHOICE;
            $points = isset($q['points']) ? (int) $q['points'] : 1;
            $options = isset($q['options']) && \is_array($q['options']) ? $q['options'] : null;
            $correctAnswer = isset($q['correctAnswer']) && \is_array($q['correctAnswer']) ? $q['correctAnswer'] : [];

            if ($questionText === '') {
                continue;
            }

            $question = new Question();
            $question->setExam($exam);
            $question->setQuestionText($questionText);
            $question->setQuestionType($type);
            $question->setPoints(max(0, $points));
            $question->setOptions($options);
            $question->setCorrectAnswer($correctAnswer);
            $question->setSortOrder($sortOrder++);

            $this->entityManager->persist($question);
            $exam->addQuestion($question);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Questions added',
            'exam_id' => $exam->getId(),
            'questions_count' => $exam->getQuestions()->count(),
        ], Response::HTTP_CREATED);
    }

    /**
     * GET /api/teacher/exams/{id}/results → tous les résultats + stats
     */
    #[Route('/exams/{id}/results', name: 'exam_results', methods: ['GET'])]
    public function results(string $id): JsonResponse
    {
        $exam = $this->examRepository->find($id);
        if (!$exam instanceof Exam) {
            return $this->json(['message' => 'Exam not found'], Response::HTTP_NOT_FOUND);
        }

        $course = $exam->getCourse();
        if ($course instanceof Course) {
            $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);
        }

        $attempts = $exam->getAttempts();
        $results = [];
        $totalScore = 0.0;
        $passedCount = 0;

        foreach ($attempts as $attempt) {
            if ($attempt->getSubmittedAt() === null) {
                continue;
            }
            $student = $attempt->getStudent();
            $results[] = [
                'attempt_id' => $attempt->getId(),
                'student_id' => $student?->getId(),
                'student_email' => $student?->getEmail(),
                'student_name' => $student ? $student->getFirstName() . ' ' . $student->getLastName() : '',
                'started_at' => $attempt->getStartedAt()?->format(\DateTimeInterface::ATOM),
                'submitted_at' => $attempt->getSubmittedAt()?->format(\DateTimeInterface::ATOM),
                'score' => $attempt->getScore(),
                'is_passed' => $attempt->isPassed(),
            ];
            if ($attempt->getScore() !== null) {
                $totalScore += $attempt->getScore();
                if ($attempt->isPassed()) {
                    $passedCount++;
                }
            }
        }

        $submittedCount = \count($results);
        $averageScore = $submittedCount > 0 ? $totalScore / $submittedCount : null;

        return $this->json([
            'exam_id' => $exam->getId(),
            'exam_title' => $exam->getTitle(),
            'attempts' => $results,
            'stats' => [
                'total_attempts' => $attempts->count(),
                'submitted_count' => $submittedCount,
                'passed_count' => $passedCount,
                'average_score' => $averageScore !== null ? round($averageScore, 2) : null,
            ],
        ]);
    }

    private function serializeExam(Exam $e): array
    {
        return [
            'id' => $e->getId(),
            'title' => $e->getTitle(),
            'description' => $e->getDescription(),
            'course_id' => $e->getCourse()?->getId(),
            'duration' => $e->getDuration(),
            'passing_score' => $e->getPassingScore(),
            'is_published' => $e->isPublished(),
            'available_from' => $e->getAvailableFrom()?->format(\DateTimeInterface::ATOM),
            'available_to' => $e->getAvailableTo()?->format(\DateTimeInterface::ATOM),
            'created_at' => $e->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
