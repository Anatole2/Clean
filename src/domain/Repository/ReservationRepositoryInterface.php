<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Reservation;
use DateTimeImmutable;

interface ReservationRepositoryInterface
{
    public function findById(int $id): ?Reservation;


    public function findActiveForUser(string $userId, string $parkingId, DateTimeImmutable $atTime): ?Reservation;
}

