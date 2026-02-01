<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ExamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [])]
#[ORM\Entity(repositoryClass: ExamRepository::class)]
#[ORM\Table(name: 'exams')]
class Exam
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    private string $id;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'exams')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Course $course = null;

    /** Durée en minutes */
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $duration = null;

    /** Score minimum pour réussir (%) */
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Range(min: 0, max: 100)]
    private int $passingScore = 60;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isPublished = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $availableFrom = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $availableTo = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Question>
     */
    #[ORM\OneToMany(targetEntity: Question::class, mappedBy: 'exam', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $questions;

    /**
     * @var Collection<int, StudentExamAttempt>
     */
    #[ORM\OneToMany(targetEntity: StudentExamAttempt::class, mappedBy: 'exam', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $attempts;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->questions = new ArrayCollection();
        $this->attempts = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getPassingScore(): int
    {
        return $this->passingScore;
    }

    public function setPassingScore(int $passingScore): static
    {
        $this->passingScore = $passingScore;
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;
        return $this;
    }

    public function getAvailableFrom(): ?\DateTimeImmutable
    {
        return $this->availableFrom;
    }

    public function setAvailableFrom(?\DateTimeImmutable $availableFrom): static
    {
        $this->availableFrom = $availableFrom;
        return $this;
    }

    public function getAvailableTo(): ?\DateTimeImmutable
    {
        return $this->availableTo;
    }

    public function setAvailableTo(?\DateTimeImmutable $availableTo): static
    {
        $this->availableTo = $availableTo;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): static
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setExam($this);
        }
        return $this;
    }

    public function removeQuestion(Question $question): static
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getExam() === $this) {
                $question->setExam(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, StudentExamAttempt>
     */
    public function getAttempts(): Collection
    {
        return $this->attempts;
    }
}
