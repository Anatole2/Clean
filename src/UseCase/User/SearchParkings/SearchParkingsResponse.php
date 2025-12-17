<?php

declare(strict_types=1);

namespace App\UseCase\User\SearchParkings;

class SearchParkingsResponse
{
  /**
   * @param SearchParkingsResult[] $parkings
   */
  public function __construct(
    public array $parkings
  ) {}
}
