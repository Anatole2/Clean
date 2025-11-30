<?php

declare(strict_types=1);

namespace App\UseCase\Owner\CreateParking;

class CreateParkingRequest
{
  /**
   * @param array $priceGridConfig Configuration brute du JSON des prix
   * @param array $openingHoursConfig Configuration brute du JSON des horaires
   */
  public function __construct(
    public string $ownerId,
    public string $name,
    public float $latitude,
    public float $longitude,
    public int $totalPlaces,
    public array $priceGridConfig,
    public array $openingHoursConfig
  ) {}
}
