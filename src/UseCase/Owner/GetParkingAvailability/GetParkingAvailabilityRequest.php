<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetParkingAvailability;

use DateTimeImmutable;

class GetParkingAvailabilityRequest
{
  public function __construct(
    public string $parkingId,
    public string $ownerId,
    public DateTimeImmutable $checkTime
  ) {}
}
