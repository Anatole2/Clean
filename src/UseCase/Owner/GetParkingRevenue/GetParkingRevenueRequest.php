<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetParkingRevenue;

class GetParkingRevenueRequest
{
  public function __construct(
    public string $parkingId,
    public string $ownerId,
    public int $month, // 1 à 12
    public int $year   // ex: 2025
  ) {}
}
