<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Classroom;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Classroom>
 */
class ClassroomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classroom::class);
    }

    /**
     * @return Classroom[]
     */
    public function findByTeacher(User $teacher): array
    {
        return $this->findBy(
            ['teacher' => $teacher],
            ['createdAt' => 'DESC']
        );
    }
}
