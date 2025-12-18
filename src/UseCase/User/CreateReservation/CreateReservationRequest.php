<?php

declare(strict_types=1);

namespace App\UseCase\User\CreateReservation;

use DateTimeImmutable;

class CreateReservationRequest
{
  public function __construct(
    public string $userId,
    public string $parkingId,
    public DateTimeImmutable $startTime,
    public DateTimeImmutable $endTime
  ) {
    if ($endTime <= $startTime) {
      throw new \InvalidArgumentException("La date de fin doit être après la date de début.");
    }

    if ($startTime < new DateTimeImmutable()) {
      throw new \InvalidArgumentException("Impossible de réserver dans le passé.");
    }
  }
}
