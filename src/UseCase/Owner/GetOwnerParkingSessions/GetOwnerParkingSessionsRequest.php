<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetOwnerParkingSessions;

class GetOwnerParkingSessionsRequest
{
  public function __construct(
    public string $parkingId,
    public string $ownerId
  ) {}
}
