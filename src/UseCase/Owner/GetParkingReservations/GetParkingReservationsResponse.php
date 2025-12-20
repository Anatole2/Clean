<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetParkingReservations;

use App\Domain\Entity\Parking;

class GetParkingReservationsResponse
{
  public function __construct(
    public Parking $parking,
    public array $reservations
  ) {}
}
