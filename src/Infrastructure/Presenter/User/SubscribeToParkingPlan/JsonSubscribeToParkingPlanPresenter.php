<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\User\SubscribeToParkingPlan;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\User\SubscribeToParkingPlan\SubscribeToParkingPlanResponse;

class JsonSubscribeToParkingPlanPresenter implements PresenterInterface
{
  /**
   * @param SubscribeToParkingPlanResponse $response
   */
  public function present($response): string
  {
    return json_encode([
      'status' => 'success',
      'message' => 'Souscription réussie.',
      'data' => [
        'subscription_id' => $response->subscription->getId(),
        'plan_name' => $response->subscription->getPlanName(),
        'price' => $response->subscription->getPrice() / 100, // Conversion centimes -> euros
        'start_date' => $response->subscription->getStartDate()->format('Y-m-d'),
        'end_date' => $response->subscription->getEndDate()->format('Y-m-d')
      ]
    ]);
  }
}
