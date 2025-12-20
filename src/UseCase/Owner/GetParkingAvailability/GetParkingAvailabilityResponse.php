<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetParkingAvailability;

use App\Domain\Entity\Parking;
use DateTimeImmutable;

class GetParkingAvailabilityResponse
{
  public function __construct(
    public Parking $parking,
    public DateTimeImmutable $checkTime,
    public int $totalPlaces,
    public int $reservedPlaces,
    public int $subscribedPlaces,
    public int $squatterPlaces,
    public int $availablePlaces
  ) {}
}
