<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetParkingReservations;

class GetParkingReservationsRequest
{
  public function __construct(
    public string $parkingId,
    public string $ownerId
  ) {}
}
