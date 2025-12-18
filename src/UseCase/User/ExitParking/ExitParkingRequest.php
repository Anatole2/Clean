<?php

declare(strict_types=1);

namespace App\UseCase\User\ExitParking;

class ExitParkingRequest
{
  public function __construct(
    public string $userId,
    public string $parkingId
  ) {}
}
