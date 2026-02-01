<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StudentAnswerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [])]
#[ORM\Entity(repositoryClass: StudentAnswerRepository::class)]
#[ORM\Table(name: 'student_answers')]
class StudentAnswer
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: StudentExamAttempt::class, inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?StudentExamAttempt $attempt = null;

    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'studentAnswers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Question $question = null;

    /** Réponse de l'étudiant (format selon type de question) */
    #[ORM\Column(type: Types::JSON)]
    private array $answer = [];

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $isCorrect = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $pointsEarned = null;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAttempt(): ?StudentExamAttempt
    {
        return $this->attempt;
    }

    public function setAttempt(?StudentExamAttempt $attempt): static
    {
        $this->attempt = $attempt;
        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;
        return $this;
    }

    public function getAnswer(): array
    {
        return $this->answer;
    }

    public function setAnswer(array $answer): static
    {
        $this->answer = $answer;
        return $this;
    }

    public function isCorrect(): ?bool
    {
        return $this->isCorrect;
    }

    public function setIsCorrect(?bool $isCorrect): static
    {
        $this->isCorrect = $isCorrect;
        return $this;
    }

    public function getPointsEarned(): ?float
    {
        return $this->pointsEarned;
    }

    public function setPointsEarned(?float $pointsEarned): static
    {
        $this->pointsEarned = $pointsEarned;
        return $this;
    }
}
