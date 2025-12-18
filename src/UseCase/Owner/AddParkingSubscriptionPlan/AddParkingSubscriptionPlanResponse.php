<?php

declare(strict_types=1);

namespace App\UseCase\Owner\AddParkingSubscriptionPlan;

use App\Domain\Entity\Parking;
use App\Domain\ValueObject\SubscriptionPlan;

class AddParkingSubscriptionPlanResponse
{
  public function __construct(
    public string $parkingId,
    public array $subscriptionPlans,
  ) {}

  public static function fromParking(Parking $parking): self
  {
    return new self(
      $parking->getId(),
      array_map(
        fn(SubscriptionPlan $plan) => $plan->toArray(),
        $parking->getSubscriptionPlans()
      )
    );
  }
}
