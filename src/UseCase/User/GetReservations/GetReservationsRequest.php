<?php

declare(strict_types=1);

namespace App\UseCase\User\GetReservations;

class GetReservationsRequest
{
  public function __construct(
    public string $userId
  ) {}
}
