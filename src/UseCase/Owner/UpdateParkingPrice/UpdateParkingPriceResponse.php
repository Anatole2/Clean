<?php

declare(strict_types=1);

namespace App\UseCase\Owner\UpdateParkingPrice;

use App\Domain\Entity\Parking;

class UpdateParkingPriceResponse
{
  public function __construct(
    public Parking $parking
  ) {}

  public static function fromParking(Parking $parking): self
  {
    return new self($parking);
  }
}
