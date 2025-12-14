<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Stationnement;

interface StationnementRepositoryInterface
{
   
    public function findActiveForUser(string $userId, string $parkingId): ?Stationnement;

   
    public function save(Stationnement $stationnement): void;
}

