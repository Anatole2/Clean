<?php

declare(strict_types=1);

namespace App\UseCase\Owner\CreateParking;

use App\Domain\Entity\Parking;

class CreateParkingResponse
{
  public function __construct(
    public string $id,
    public string $name,
    public int $totalPlaces,
    public float $latitude,
    public float $longitude,
    public array $priceGrid,
    public array $openingHours
  ) {}

  public static function fromParking(Parking $parking): self
  {
    return new self(
      id: $parking->getId(),
      name: $parking->getName(),
      totalPlaces: $parking->getTotalPlaces(),
      // On extrait les valeurs primitives des Value Objects
      latitude: $parking->getCoordinates()->getLatitude(),
      longitude: $parking->getCoordinates()->getLongitude(),
      priceGrid: $parking->getPriceGrid()->toArray(),
      openingHours: $parking->getOpeningHours()->toArray()
    );
  }
}
