<?php

declare(strict_types=1);

namespace App\UseCase\User\EnterParking;

use App\Domain\Entity\ParkingSession;

class EnterParkingResponse
{
  public function __construct(public ParkingSession $session) {}
}
