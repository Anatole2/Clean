<?php

namespace App\Domain\Service;

use App\Domain\Entity\Parking;
use DateTimeImmutable;

interface AvailabilityCheckerInterface
{
    public function isAvailable(Parking $parking, DateTimeImmutable $start, DateTimeImmutable $end): bool;
}