<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\GetParkingAvailability;

use App\Infrastructure\Presenter\PresenterInterface;
use Twig\Environment;

class HtmlGetParkingAvailabilityPresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}

  public function present($response): string
  {
    return $this->twig->render('owner/parking_availability.html.twig', [
      'parking' => $response->parking,
      'checkTime' => $response->checkTime,
      'total' => $response->totalPlaces,
      'reserved' => $response->reservedPlaces,
      'subscribed' => $response->subscribedPlaces,
      'squatters' => $response->squatterPlaces,
      'available' => $response->availablePlaces
    ]);
  }
}
