<?php

declare(strict_types=1);

namespace App\UseCase\User\SubscribeToParkingPlan;

class SubscribeToParkingPlanRequest
{
  public function __construct(
    public string $userId,
    public string $parkingId,
    public string $planId,
    public string $startDate,
    public string $endDate
  ) {}
}
