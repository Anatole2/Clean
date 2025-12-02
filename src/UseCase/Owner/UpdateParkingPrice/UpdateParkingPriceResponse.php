<?php

declare(strict_types=1);

namespace App\UseCase\Owner\UpdateParkingPrice;

use App\Domain\Entity\Parking;

class UpdateParkingPriceResponse
{
  public function __construct(
    public string $id,
    public array $priceGrid
  ) {}

  public static function fromParking(Parking $parking): self
  {
    return new self(
      $parking->getId(),
      $parking->getPriceGrid()->toArray()
    );
  }
}
