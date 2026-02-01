<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Classroom;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ClassroomVoter extends Voter
{
    public const VIEW = 'CLASSROOM_VIEW';
    public const EDIT = 'CLASSROOM_EDIT';
    public const DELETE = 'CLASSROOM_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)) {
            return false;
        }
        return $subject instanceof Classroom;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Classroom $classroom */
        $classroom = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($classroom, $user),
            self::EDIT, self::DELETE => $this->canEdit($classroom, $user),
            default => false,
        };
    }

    private function canView(Classroom $classroom, User $user): bool
    {
        // Enseignant propriétaire OU étudiant inscrit dans la classe
        if ($classroom->getTeacher()?->getId() === $user->getId()) {
            return true;
        }
        return $classroom->getStudents()->exists(
            fn (int $_, User $student): bool => $student->getId() === $user->getId()
        );
    }

    private function canEdit(Classroom $classroom, User $user): bool
    {
        // Enseignant propriétaire uniquement
        return $classroom->getTeacher()?->getId() === $user->getId();
    }
}
