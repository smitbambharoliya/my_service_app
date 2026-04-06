<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class GamificationService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Award points to a user and save them.
     */
    public function awardPoints(User $user, int $points, bool $flush = true): void
    {
        $currentPoints = $user->getReputationPoints();
        $user->setReputationPoints($currentPoints + $points);
        // The setReputationPoints method handles internal tier upgrading logic automatically

        if ($flush) {
            $this->em->flush();
        }
    }
}
