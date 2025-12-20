<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetUnauthorizedParkers;

use App\Domain\Entity\Parking;
use App\Domain\Entity\ParkingSession;

class GetUnauthorizedParkersResponse
{
  /**
   * @param Parking $parking
   * @param ParkingSession[] $squatters Liste des sessions actives sans autorisation
   */
  public function __construct(
    public Parking $parking,
    public array $squatters
  ) {}
}
