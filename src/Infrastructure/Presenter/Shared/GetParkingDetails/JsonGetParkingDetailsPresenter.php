<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Shared\GetParkingDetails;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\Shared\GetParkingDetails\GetParkingDetailsResponse;

class JsonGetParkingDetailsPresenter implements PresenterInterface
{
  public function present(object $response): string
  {
    if (!$response instanceof GetParkingDetailsResponse) {
      throw new \InvalidArgumentException("Mauvais type de réponse.");
    }

    $parking = $response->parking;

    return json_encode([
      'id' => $parking->getId(),
      'name' => $parking->getName(),
      'location' => [
        'lat' => $parking->getCoordinates()->getLatitude(),
        'lon' => $parking->getCoordinates()->getLongitude()
      ],
      'total_places' => $parking->getTotalPlaces(),
      'opening_hours' => $parking->getOpeningHours()->toArray(),
      'price_grid' => $parking->getPriceGrid()->toArray(),
      'subscription_plans' => array_map(fn($plan) => [
        'id' => $plan->getId(),
        'name' => $plan->getName(),
        'price_per_month' => $plan->getMonthlyPrice() / 100 . ' €',
      ], $parking->getSubscriptionPlans())
    ]);
  }
}
