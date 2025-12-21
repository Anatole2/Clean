<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\AddParkingSubscriptionPlan;

use App\Infrastructure\Presenter\PresenterInterface;

class JsonAddParkingSubscriptionPlanPresenter implements PresenterInterface
{
  public function present(object $responseDTO): string
  {
    header('Content-Type: application/json');

    return json_encode([
      'status' => 'success',
      'message' => 'Plan d\'abonnement ajouté avec succès.',
      'data' => [
        'parking_id' => $responseDTO->parkingId,
        'total_plans' => count($responseDTO->subscriptionPlans),
        'plans' => $responseDTO->subscriptionPlans
      ]
    ]);
  }
}
