<?php

declare(strict_types=1);

namespace App\UseCase\User\SubscribeToParkingPlan;

class SubscribeToParkingPlanRequest
{
  public function __construct(
    public string $userId,
    public string $parkingId,
    public string $planId, // On identifie le plan par son nom unique dans le parking
    public string $startDate // Format Y-m-d
  ) {}
}
