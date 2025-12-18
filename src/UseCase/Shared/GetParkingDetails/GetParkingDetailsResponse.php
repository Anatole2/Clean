<?php

declare(strict_types=1);

namespace App\UseCase\Shared\GetParkingDetails;

use App\Domain\Entity\Parking;

class GetParkingDetailsResponse
{
  public function __construct(
    public Parking $parking
  ) {}
}
