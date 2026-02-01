<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Course;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    /**
     * Tous les cours des classes dont l'enseignant est propriétaire.
     *
     * @return Course[]
     */
    public function findByTeacher(User $teacher): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.classroom', 'cl')
            ->where('cl.teacher = :teacher')
            ->setParameter('teacher', $teacher)
            ->orderBy('cl.name', 'ASC')
            ->addOrderBy('c.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
