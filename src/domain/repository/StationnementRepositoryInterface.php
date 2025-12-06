<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Stationnement;
use App\Domain\Entity\User;

interface StationnementRepositoryInterface
{
    /**
     * Récupère tous les stationnements terminés d'un utilisateur (pour l'historique).
     * @return Stationnement[]
     */
    public function findCompletedByUser(User $user): array;
}