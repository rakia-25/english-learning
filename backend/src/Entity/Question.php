<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [])]
#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\Table(name: 'questions')]
class Question
{
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_TRUE_FALSE = 'true_false';
    public const TYPE_SHORT_ANSWER = 'short_answer';

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Exam::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Exam $exam = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $questionText = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: [self::TYPE_MULTIPLE_CHOICE, self::TYPE_TRUE_FALSE, self::TYPE_SHORT_ANSWER])]
    private ?string $questionType = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\PositiveOrZero]
    private int $points = 1;

    /** Options pour QCM (tableau de réponses possibles) */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $options = null;

    /** Réponse(s) correcte(s) - format selon le type (index, booléen, texte) */
    #[ORM\Column(type: Types::JSON)]
    private array $correctAnswer = [];

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, options: ['default' => 0])]
    private int $sortOrder = 0;

    /**
     * @var Collection<int, StudentAnswer>
     */
    #[ORM\OneToMany(targetEntity: StudentAnswer::class, mappedBy: 'question', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $studentAnswers;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->studentAnswers = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
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

    public function getQuestionText(): ?string
    {
        return $this->questionText;
    }

    public function setQuestionText(string $questionText): static
    {
        $this->questionText = $questionText;
        return $this;
    }

    public function getQuestionType(): ?string
    {
        return $this->questionType;
    }

    public function setQuestionType(string $questionType): static
    {
        $this->questionType = $questionType;
        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;
        return $this;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function getCorrectAnswer(): array
    {
        return $this->correctAnswer;
    }

    public function setCorrectAnswer(array $correctAnswer): static
    {
        $this->correctAnswer = $correctAnswer;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }
}
