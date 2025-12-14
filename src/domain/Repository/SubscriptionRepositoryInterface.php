<?php

namespace App\Domain\Repository;

use DateTimeImmutable;

interface SubscriptionRepositoryInterface
{
   
    public function countActiveForParking(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): int;
}

