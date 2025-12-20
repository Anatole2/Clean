<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetParkingRevenue;

use App\Domain\Entity\Parking;

class GetParkingRevenueResponse
{
  public function __construct(
    public Parking $parking,
    public int $month,
    public int $year,
    public int $revenueReservations, // En centimes
    public int $revenueSubscriptions, // En centimes
    public int $totalRevenue // En centimes
  ) {}
}
