<?php

declare(strict_types=1);

namespace App\UseCase\User\GetReservations;



class GetReservationsResponse
{
  /**
   * @param ReservationSummary[] $reservations
   */
  public function __construct(
    public array $reservations
  ) {}
}
