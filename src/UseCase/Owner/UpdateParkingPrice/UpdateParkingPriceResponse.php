<?php

declare(strict_types=1);

namespace App\UseCase\Owner\UpdateParkingPrice;

use App\Domain\Entity\Parking;

class UpdateParkingPriceResponse
{
  // ✅ On stocke l'objet Parking complet en public
  public function __construct(
    public Parking $parking
  ) {}

  public static function fromParking(Parking $parking): self
  {
    // On injecte l'entité directement
    return new self($parking);
  }
}
