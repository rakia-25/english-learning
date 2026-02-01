<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Classroom;
use App\Entity\Course;
use App\Entity\Exam;
use App\Entity\Question;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixtures de test : teachers, students, classes, cours, examens.
 */
class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $teachers = [];
        $students = [];

        // 2 enseignants
        foreach (['teacher1@test.com', 'teacher2@test.com'] as $i => $email) {
            $teacher = new User();
            $teacher->setEmail($email);
            $teacher->setPassword($this->passwordHasher->hashPassword($teacher, 'password123'));
            $teacher->setFirstName('Enseignant');
            $teacher->setLastName('Test ' . ($i + 1));
            $teacher->setRoles(['ROLE_TEACHER']);
            $teacher->setIsVerified(true);
            $manager->persist($teacher);
            $teachers[] = $teacher;
        }

        // 5 étudiants
        for ($i = 1; $i <= 5; $i++) {
            $student = new User();
            $student->setEmail("student{$i}@test.com");
            $student->setPassword($this->passwordHasher->hashPassword($student, 'password123'));
            $student->setFirstName('Étudiant');
            $student->setLastName("Test {$i}");
            $student->setRoles(['ROLE_STUDENT']);
            $student->setIsVerified(true);
            $manager->persist($student);
            $students[] = $student;
        }

        $manager->flush();

        // 2 classes (1 par enseignant)
        $classrooms = [];
        foreach (['Classe PHP/Symfony', 'Classe React/JavaScript'] as $i => $name) {
            $classroom = new Classroom();
            $classroom->setName($name);
            $classroom->setDescription("Description de la classe : {$name}");
            $classroom->setTeacher($teachers[$i]);
            $classroom->setIsActive(true);
            $classroom->generateCode();
            $manager->persist($classroom);
            $classrooms[] = $classroom;

            // Inscrire les 3 premiers étudiants dans la classe 1, les 2 suivants dans la classe 2
            $start = $i === 0 ? 0 : 3;
            $end = $i === 0 ? 3 : 5;
            for ($j = $start; $j < $end; $j++) {
                $classroom->addStudent($students[$j]);
            }
        }

        $manager->flush();

        // 3 cours par classe + 1 examen avec 5 questions par cours
        $courseTitles = [
            ['Introduction PHP', 'POO en PHP', 'Symfony bases'],
            ['JavaScript moderne', 'React hooks', 'API REST'],
        ];
        $courseDescriptions = [
            ['Découverte de PHP', 'Programmation orientée objet', 'Framework Symfony'],
            ['ES6 et plus', 'Composants et hooks', 'Consommer des APIs'],
        ];

        foreach ($classrooms as $classIndex => $classroom) {
            for ($c = 0; $c < 3; $c++) {
                $course = new Course();
                $course->setTitle($courseTitles[$classIndex][$c]);
                $course->setDescription($courseDescriptions[$classIndex][$c]);
                $course->setContent("<h1>{$courseTitles[$classIndex][$c]}</h1><p>Contenu du cours pour les fixtures.</p>");
                $course->setClassroom($classroom);
                $course->setSortOrder($c + 1);
                $course->setIsPublished(true);
                $course->setPublishedAt(new \DateTimeImmutable('-1 day'));
                $manager->persist($course);
                $classroom->addCourse($course);

                // 1 examen avec 5 questions variées
                $exam = new Exam();
                $exam->setTitle("Examen : {$course->getTitle()}");
                $exam->setDescription("Contrôle sur le cours {$course->getTitle()}");
                $exam->setCourse($course);
                $exam->setDuration(30);
                $exam->setPassingScore(60);
                $exam->setIsPublished(true);
                $exam->setAvailableFrom(new \DateTimeImmutable('-1 day'));
                $exam->setAvailableTo(new \DateTimeImmutable('+30 days'));
                $manager->persist($exam);

                // 5 questions : QCM, Vrai/Faux, Texte court, QCM, Vrai/Faux
                $questionsData = [
                    [
                        'type' => Question::TYPE_MULTIPLE_CHOICE,
                        'text' => "Quelle est la bonne réponse pour le cours {$course->getTitle()} ?",
                        'points' => 2,
                        'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                        'correct' => [0],
                    ],
                    [
                        'type' => Question::TYPE_TRUE_FALSE,
                        'text' => 'Cette affirmation est vraie.',
                        'points' => 1,
                        'options' => null,
                        'correct' => [true],
                    ],
                    [
                        'type' => Question::TYPE_SHORT_ANSWER,
                        'text' => "Donnez un mot-clé du cours.",
                        'points' => 2,
                        'options' => null,
                        'correct' => ['réponse'],
                    ],
                    [
                        'type' => Question::TYPE_MULTIPLE_CHOICE,
                        'text' => 'Choisissez la bonne option.',
                        'points' => 2,
                        'options' => ['Réponse 1', 'Réponse 2', 'Réponse 3'],
                        'correct' => [1],
                    ],
                    [
                        'type' => Question::TYPE_TRUE_FALSE,
                        'text' => 'Cette affirmation est fausse.',
                        'points' => 1,
                        'options' => null,
                        'correct' => [false],
                    ],
                ];

                foreach ($questionsData as $qIndex => $qData) {
                    $question = new Question();
                    $question->setExam($exam);
                    $question->setQuestionText($qData['text']);
                    $question->setQuestionType($qData['type']);
                    $question->setPoints($qData['points']);
                    $question->setOptions($qData['options']);
                    $question->setCorrectAnswer($qData['correct']);
                    $question->setSortOrder($qIndex + 1);
                    $manager->persist($question);
                    $exam->addQuestion($question);
                }
            }
        }

        $manager->flush();
    }
}
