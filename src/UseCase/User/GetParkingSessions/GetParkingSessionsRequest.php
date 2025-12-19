<?php

declare(strict_types=1);

namespace App\UseCase\User\GetParkingSessions;

class GetParkingSessionsRequest
{
  public function __construct(public string $userId) {}
}
