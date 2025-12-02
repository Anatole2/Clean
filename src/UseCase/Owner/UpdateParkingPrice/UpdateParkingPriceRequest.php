<?php

declare(strict_types=1);

namespace App\UseCase\Owner\UpdateParkingPrice;

class UpdateParkingPriceRequest
{
  public function __construct(
    public string $parkingId,
    public string $ownerId,
    public array $newPriceGridConfig
  ) {}
}
