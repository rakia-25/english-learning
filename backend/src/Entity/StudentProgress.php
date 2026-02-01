<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StudentProgressRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [])]
#[ORM\Entity(repositoryClass: StudentProgressRepository::class)]
#[ORM\Table(name: 'student_progress')]
#[ORM\UniqueConstraint(name: 'student_course_unique', columns: ['student_id', 'course_id'])]
class StudentProgress
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $student = null;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Course $course = null;

    /** Leçons / sections complétées (IDs ou repères) */
    #[ORM\Column(type: Types::JSON, options: ['default' => '[]'])]
    private array $completedLessons = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastAccessedAt = null;

    #[ORM\Column(type: Types::FLOAT, options: ['default' => 0])]
    #[Assert\Range(min: 0, max: 100)]
    private float $progressPercentage = 0.0;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
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

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;
        return $this;
    }

    /** @return list<string|int> */
    public function getCompletedLessons(): array
    {
        return $this->completedLessons;
    }

    /** @param list<string|int> $completedLessons */
    public function setCompletedLessons(array $completedLessons): static
    {
        $this->completedLessons = $completedLessons;
        return $this;
    }

    public function getLastAccessedAt(): ?\DateTimeImmutable
    {
        return $this->lastAccessedAt;
    }

    public function setLastAccessedAt(?\DateTimeImmutable $lastAccessedAt): static
    {
        $this->lastAccessedAt = $lastAccessedAt;
        return $this;
    }

    public function getProgressPercentage(): float
    {
        return $this->progressPercentage;
    }

    public function setProgressPercentage(float $progressPercentage): static
    {
        $this->progressPercentage = $progressPercentage;
        return $this;
    }
}
