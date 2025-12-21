<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetOwnerParkingSessions;

use App\Domain\Entity\Parking;

class GetOwnerParkingSessionsResponse
{
  public function __construct(
    public Parking $parking,
    public array $sessions
  ) {}
}
