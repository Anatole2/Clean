<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\User\CreateReservation;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\User\CreateReservation\CreateReservationResponse;

class JsonCreateReservationPresenter implements PresenterInterface
{
  public function present(object $response): string
  {
    /** @var CreateReservationResponse $response */
    return json_encode([
      'status' => 'success',
      'reservation_id' => $response->reservation->getId(),
      'price_paid' => $response->reservation->getPricePaidInCents() / 100 . ' €',
      'message' => 'Réservation confirmée'
    ]);
  }
}
