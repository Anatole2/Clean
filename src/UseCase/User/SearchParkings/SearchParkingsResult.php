<?php

declare(strict_types=1);

namespace App\UseCase\User\SearchParkings;

class SearchParkingsResult
{
  public function __construct(
    public string $id,
    public string $name,
    public float $latitude,
    public float $longitude,
    public int $totalPlaces,
    public string $priceLabel
  ) {}
}
