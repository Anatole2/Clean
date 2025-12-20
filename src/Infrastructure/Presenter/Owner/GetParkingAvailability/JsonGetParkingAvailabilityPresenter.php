<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\GetParkingAvailability;

use App\Infrastructure\Presenter\PresenterInterface;

class JsonGetParkingAvailabilityPresenter implements PresenterInterface
{
  public function present($response): string
  {
    return json_encode([
      'parking_name' => $response->parking->getName(),
      'check_time' => $response->checkTime->format(\DateTime::ATOM),
      'capacity' => [
        'total' => $response->totalPlaces,
        'available' => $response->availablePlaces
      ],
      'occupation' => [
        'reservations' => $response->reservedPlaces,
        'subscriptions' => $response->subscribedPlaces,
        'squatters' => $response->squatterPlaces
      ]
    ]);
  }
}
