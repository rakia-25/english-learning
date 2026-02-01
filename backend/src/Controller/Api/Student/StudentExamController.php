<?php

declare(strict_types=1);

namespace App\Controller\Api\Student;

use App\Entity\Exam;
use App\Entity\Question;
use App\Entity\StudentAnswer;
use App\Entity\StudentExamAttempt;
use App\Entity\User;
use App\Repository\ExamRepository;
use App\Repository\StudentExamAttemptRepository;
use App\Security\Voter\CourseVoter;
use App\Service\ExamRandomizerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/student', name: 'api_student_')]
#[IsGranted('ROLE_STUDENT')]
class StudentExamController extends AbstractController
{
    public function __construct(
        private readonly ExamRepository $examRepository,
        private readonly StudentExamAttemptRepository $attemptRepository,
        private readonly ExamRandomizerService $examRandomizerService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * GET /api/student/exams/{id}/start → crée tentative avec ordre randomisé, retourne questions (sans réponses correctes)
     */
    #[Route('/exams/{id}/start', name: 'exam_start', methods: ['GET'])]
    public function start(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $exam = $this->examRepository->find($id);
        if (!$exam instanceof Exam) {
            return $this->json(['message' => 'Exam not found'], Response::HTTP_NOT_FOUND);
        }

        $course = $exam->getCourse();
        if ($course === null) {
            return $this->json(['message' => 'Exam has no course'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);

        if (!$exam->isPublished()) {
            return $this->json(['message' => 'Exam is not published'], Response::HTTP_FORBIDDEN);
        }

        $now = new \DateTimeImmutable();
        if ($exam->getAvailableFrom() !== null && $now < $exam->getAvailableFrom()) {
            return $this->json(['message' => 'Exam not yet available'], Response::HTTP_FORBIDDEN);
        }
        if ($exam->getAvailableTo() !== null && $now > $exam->getAvailableTo()) {
            return $this->json(['message' => 'Exam no longer available'], Response::HTTP_FORBIDDEN);
        }

        $attempt = $this->examRandomizerService->generateAttempt($exam, $user);
        $this->entityManager->persist($attempt);
        $this->entityManager->flush();

        $questionsPayload = $this->buildQuestionsForStudent($attempt);
        return $this->json([
            'attempt_id' => $attempt->getId(),
            'duration_minutes' => $exam->getDuration(),
            'questions' => $questionsPayload,
        ]);
    }

    /**
     * POST /api/student/exams/{id}/submit : { attemptId, answers: [{ questionId, value }] } → calcule score, retourne résultat
     */
    #[Route('/exams/{id}/submit', name: 'exam_submit', methods: ['POST'])]
    public function submit(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $exam = $this->examRepository->find($id);
        if (!$exam instanceof Exam) {
            return $this->json(['message' => 'Exam not found'], Response::HTTP_NOT_FOUND);
        }

        $course = $exam->getCourse();
        if ($course !== null) {
            $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);
        }

        $data = json_decode((string) $request->getContent(), true);
        if (!\is_array($data) || empty($data['attemptId'])) {
            return $this->json(['message' => 'attemptId is required'], Response::HTTP_BAD_REQUEST);
        }

        $attempt = $this->attemptRepository->find($data['attemptId']);
        if (!$attempt instanceof StudentExamAttempt) {
            return $this->json(['message' => 'Attempt not found'], Response::HTTP_NOT_FOUND);
        }

        if ($attempt->getStudent()?->getId() !== $user->getId() || $attempt->getExam()?->getId() !== $exam->getId()) {
            return $this->json(['message' => 'Invalid attempt'], Response::HTTP_FORBIDDEN);
        }

        if ($attempt->getSubmittedAt() !== null) {
            return $this->json(['message' => 'Attempt already submitted'], Response::HTTP_BAD_REQUEST);
        }

        $answersData = $data['answers'] ?? [];
        if (!\is_array($answersData)) {
            $answersData = [];
        }

        $questionOrder = $attempt->getQuestionOrder();
        $questionsById = [];
        foreach ($exam->getQuestions() as $q) {
            $questionsById[$q->getId()] = $q;
        }

        $totalPointsEarned = 0.0;
        $totalPointsMax = 0.0;

        foreach ($questionOrder as $item) {
            $questionId = \is_array($item) ? ($item['id'] ?? null) : $item;
            if ($questionId === null) {
                continue;
            }
            $question = $questionsById[$questionId] ?? null;
            if (!$question instanceof Question) {
                continue;
            }
            $totalPointsMax += $question->getPoints();

            $optionsOrder = \is_array($item) ? ($item['options_order'] ?? null) : null;
            $studentValue = null;
            foreach ($answersData as $a) {
                if (($a['questionId'] ?? null) === $questionId) {
                    $studentValue = $a['value'] ?? $a['answer'] ?? null;
                    break;
                }
            }

            $correct = $this->isAnswerCorrect($question, $studentValue, $optionsOrder);
            $pointsEarned = $correct ? $question->getPoints() : 0.0;
            $totalPointsEarned += $pointsEarned;

            $studentAnswer = new StudentAnswer();
            $studentAnswer->setAttempt($attempt);
            $studentAnswer->setQuestion($question);
            $studentAnswer->setAnswer($studentValue !== null ? [\is_array($studentValue) ? $studentValue : [$studentValue]] : []);
            $studentAnswer->setIsCorrect($correct);
            $studentAnswer->setPointsEarned($pointsEarned);
            $this->entityManager->persist($studentAnswer);
            $attempt->addAnswer($studentAnswer);
        }

        $score = $totalPointsMax > 0 ? round(100.0 * $totalPointsEarned / $totalPointsMax, 2) : 0.0;
        $attempt->setScore($score);
        $attempt->setIsPassed($score >= $exam->getPassingScore());
        $attempt->setSubmittedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $this->json([
            'attempt_id' => $attempt->getId(),
            'score' => $attempt->getScore(),
            'is_passed' => $attempt->isPassed(),
            'points_earned' => $totalPointsEarned,
            'points_max' => $totalPointsMax,
        ]);
    }

    /**
     * GET /api/student/exams/{id}/result → voir son résultat détaillé
     */
    #[Route('/exams/{id}/result', name: 'exam_result', methods: ['GET'])]
    public function result(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $exam = $this->examRepository->find($id);
        if (!$exam instanceof Exam) {
            return $this->json(['message' => 'Exam not found'], Response::HTTP_NOT_FOUND);
        }

        $course = $exam->getCourse();
        if ($course !== null) {
            $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);
        }

        $attemptId = $request->query->get('attemptId');
        $attempt = null;
        if ($attemptId) {
            $attempt = $this->attemptRepository->find($attemptId);
        }
        if (!$attempt instanceof StudentExamAttempt) {
            $attempts = $this->attemptRepository->findBy(
                ['exam' => $exam, 'student' => $user],
                ['submittedAt' => 'DESC'],
                1
            );
            $attempt = $attempts[0] ?? null;
        }

        if (!$attempt instanceof StudentExamAttempt || $attempt->getStudent()?->getId() !== $user->getId()) {
            return $this->json(['message' => 'No result found for this exam'], Response::HTTP_NOT_FOUND);
        }

        if ($attempt->getSubmittedAt() === null) {
            return $this->json(['message' => 'Attempt not yet submitted'], Response::HTTP_BAD_REQUEST);
        }

        $answersDetail = [];
        foreach ($attempt->getAnswers() as $a) {
            $q = $a->getQuestion();
            $answersDetail[] = [
                'question_id' => $q?->getId(),
                'question_text' => $q?->getQuestionText(),
                'points' => $q?->getPoints(),
                'points_earned' => $a->getPointsEarned(),
                'is_correct' => $a->isCorrect(),
            ];
        }

        return $this->json([
            'attempt_id' => $attempt->getId(),
            'exam_id' => $exam->getId(),
            'exam_title' => $exam->getTitle(),
            'started_at' => $attempt->getStartedAt()?->format(\DateTimeInterface::ATOM),
            'submitted_at' => $attempt->getSubmittedAt()?->format(\DateTimeInterface::ATOM),
            'score' => $attempt->getScore(),
            'is_passed' => $attempt->isPassed(),
            'answers' => $answersDetail,
        ]);
    }

    private function buildQuestionsForStudent(StudentExamAttempt $attempt): array
    {
        $exam = $attempt->getExam();
        if ($exam === null) {
            return [];
        }

        $questionsById = [];
        foreach ($exam->getQuestions() as $q) {
            $questionsById[$q->getId()] = $q;
        }

        $out = [];
        foreach ($attempt->getQuestionOrder() as $item) {
            $questionId = \is_array($item) ? ($item['id'] ?? null) : $item;
            if ($questionId === null) {
                continue;
            }
            $question = $questionsById[$questionId] ?? null;
            if (!$question instanceof Question) {
                continue;
            }

            $payload = [
                'id' => $question->getId(),
                'question_text' => $question->getQuestionText(),
                'question_type' => $question->getQuestionType(),
                'points' => $question->getPoints(),
            ];

            if ($question->getQuestionType() === Question::TYPE_MULTIPLE_CHOICE && $question->getOptions() !== null) {
                $options = $question->getOptions();
                $optionsOrder = \is_array($item) ? ($item['options_order'] ?? null) : null;
                if ($optionsOrder !== null && \is_array($optionsOrder)) {
                    $shuffled = [];
                    foreach ($optionsOrder as $idx) {
                        if (isset($options[$idx])) {
                            $shuffled[] = $options[$idx];
                        }
                    }
                    $payload['options'] = $shuffled;
                } else {
                    $payload['options'] = $options;
                }
            }

            $out[] = $payload;
        }

        return $out;
    }

    private function isAnswerCorrect(Question $question, mixed $studentValue, ?array $optionsOrder): bool
    {
        $correct = $question->getCorrectAnswer();
        $type = $question->getQuestionType();

        if ($type === Question::TYPE_MULTIPLE_CHOICE) {
            // Student sends index in shuffled options; map back to original index
            $studentIndex = is_numeric($studentValue) ? (int) $studentValue : null;
            if ($studentIndex === null) {
                return false;
            }
            if ($optionsOrder !== null && isset($optionsOrder[$studentIndex])) {
                $originalIndex = $optionsOrder[$studentIndex];
                return \in_array($originalIndex, $correct, true);
            }
            return \in_array($studentIndex, $correct, true);
        }

        if ($type === Question::TYPE_TRUE_FALSE) {
            $expected = $correct[0] ?? null;
            $given = $studentValue;
            if (\is_string($given)) {
                $given = strtolower($given) === 'true';
            }
            return $given === $expected;
        }

        if ($type === Question::TYPE_SHORT_ANSWER) {
            $given = \is_string($studentValue) ? trim($studentValue) : (string) $studentValue;
            foreach ($correct as $expected) {
                if (strcasecmp(trim((string) $expected), $given) === 0) {
                    return true;
                }
            }
            return false;
        }

        return false;
    }
}
