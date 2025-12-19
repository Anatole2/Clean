<?php

declare(strict_types=1);

namespace App\UseCase\User\GetParkingSessions;

class GetParkingSessionsResponse
{
  /** @param ParkingSessionDto[] $sessions */
  public function __construct(public array $sessions) {}
}
