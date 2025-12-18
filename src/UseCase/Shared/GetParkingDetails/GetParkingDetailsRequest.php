<?php

declare(strict_types=1);

namespace App\UseCase\Shared\GetParkingDetails;

class GetParkingDetailsRequest
{
  public function __construct(
    public string $parkingId,
  ) {}
}
