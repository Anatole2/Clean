<?php

declare(strict_types=1);

namespace App\UseCase\Owner\UpdateParkingHours;

use App\Domain\Entity\Parking;

class UpdateParkingHoursResponse
{
  public function __construct(
    public string $id,
    public array $openingHours
  ) {}

  public static function fromParking(Parking $parking): self
  {
    return new self(
      $parking->getId(),
      $parking->getOpeningHours()->toArray()
    );
  }
}
