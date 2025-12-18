<?php

declare(strict_types=1);

namespace App\UseCase\User\ExitParking;

use App\Domain\Entity\ParkingSession;

class ExitParkingResponse
{
  public function __construct(
    public ParkingSession $session,
    public int $overstayMinutes,
    public int $extraCost,
    public bool $penaltyApplied
  ) {}
}
