<?php

declare(strict_types=1);

namespace App\UseCase\User\GetParkingSessions;

class ParkingSessionDto
{
  public function __construct(
    public string $id,
    public string $parkingName,
    public string $entryTime,
    public ?string $exitTime,
    public string $duration,
    public float $pricePaid,
    public string $status,
    public bool $isActive
  ) {}
}
