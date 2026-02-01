<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StudentExamAttemptRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [])]
#[ORM\Entity(repositoryClass: StudentExamAttemptRepository::class)]
#[ORM\Table(name: 'student_exam_attempts')]
class StudentExamAttempt
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $student = null;

    #[ORM\ManyToOne(targetEntity: Exam::class, inversedBy: 'attempts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Exam $exam = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    /** Score en % (null tant que non corrigé) */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $score = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $isPassed = null;

    /** Ordre des questions (IDs) pour cette tentative (randomisé) */
    #[ORM\Column(type: Types::JSON)]
    private array $questionOrder = [];

    /**
     * @var Collection<int, StudentAnswer>
     */
    #[ORM\OneToMany(targetEntity: StudentAnswer::class, mappedBy: 'attempt', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $answers;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->answers = new ArrayCollection();
        $this->startedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStudent(): ?User
    {
        return $this->student;
    }

    public function setStudent(?User $student): static
    {
        $this->student = $student;
        return $this;
    }

    public function getExam(): ?Exam
    {
        return $this->exam;
    }

    public function setExam(?Exam $exam): static
    {
        $this->exam = $exam;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;
        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function isPassed(): ?bool
    {
        return $this->isPassed;
    }

    public function setIsPassed(?bool $isPassed): static
    {
        $this->isPassed = $isPassed;
        return $this;
    }

    /** @return list<string|array{id: string, options_order?: int[]}> */
    public function getQuestionOrder(): array
    {
        return $this->questionOrder;
    }

    /** @param list<string|array{id: string, options_order?: int[]}> $questionOrder */
    public function setQuestionOrder(array $questionOrder): static
    {
        $this->questionOrder = $questionOrder;
        return $this;
    }

    /**
     * @return Collection<int, StudentAnswer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(StudentAnswer $answer): static
    {
        if (!$this->answers->contains($answer)) {
            $this->answers->add($answer);
            $answer->setAttempt($this);
        }
        return $this;
    }

    public function removeAnswer(StudentAnswer $answer): static
    {
        if ($this->answers->removeElement($answer)) {
            if ($answer->getAttempt() === $this) {
                $answer->setAttempt(null);
            }
        }
        return $this;
    }
}
