<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetOwnerParkings;

class GetOwnerParkingsResponse
{
  /**
   * @param array<int, array{id: string, name: string, city: string, totalPlaces: int}> $parkings
   */
  public function __construct(
    public array $parkings
  ) {}
}
