<?php

declare(strict_types=1);

namespace App\UseCase\User\CreateReservation;

use App\Domain\Entity\Reservation;

class CreateReservationResponse
{
  public function __construct(
    public Reservation $reservation
  ) {}
}
