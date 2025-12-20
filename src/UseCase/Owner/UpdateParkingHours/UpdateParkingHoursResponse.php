<?php

namespace App\UseCase\Owner\UpdateParkingHours;

use App\Domain\Entity\Parking;

class UpdateParkingHoursResponse
{
  public function __construct(
    public Parking $parking
  ) {}

  public static function fromParking(Parking $parking): self
  {
    return new self($parking);
  }
}
