<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Stationnement;
use DateTimeImmutable;

interface StationnementRepositoryInterface
{
    
    public function findActiveForUser(string $userId, string $parkingId): ?Stationnement;

    
    public function save(Stationnement $stationnement): void;

  
    public function findById(int $id): ?Stationnement;

   
    public function countActiveOverlapping(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): int;
}

