<?php

declare(strict_types=1);

namespace App\UseCase\User\SubscribeToParkingPlan;

use App\Domain\Entity\UserSubscription;

class SubscribeToParkingPlanResponse
{
  public function __construct(
    public UserSubscription $subscription
  ) {}
}
