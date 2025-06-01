<?php

namespace App\Service;

class CoachService
{
    public function seeAllCoachs($entityManager)
    {
        $query = $entityManager->createQuery(
            "SELECT u
            FROM App\Entity\Users u
            WHERE u.role = 3"
        );

        return $query->getResult();
    }

    public function seeAllActiveCoachs($entityManager)
    {
        $query = $entityManager->createQuery(
            "SELECT u
            FROM App\Entity\Users u
            WHERE u.role = 3
            AND u.status = 'active'"
        );

        return $query->getResult();
    }

    public function seeAllExercisesByCoach($entityManager, $coach_id)
    {
        $query = $entityManager->createQuery(
            "SELECT u
            FROM App\Entity\Users u
            WHERE user_id = :coach_id"
        )
            ->setParameters([
                'coach_id' => $coach_id,
            ]);
        return $query->getResult();
    }
}
