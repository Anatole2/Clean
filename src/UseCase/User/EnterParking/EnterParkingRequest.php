<?php

declare(strict_types=1);

namespace App\UseCase\User\EnterParking;

class EnterParkingRequest
{
  public function __construct(public string $userId, public string $parkingId) {}
}
