<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\User\GetReservations;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\User\GetReservations\GetUserReservationsResponse; // Attention au namespace de ta Response

class JsonGetReservationsPresenter implements PresenterInterface
{
  /**
   * @param GetUserReservationsResponse $response
   */
  public function present($response): string
  {
    // Les DTOs ReservationSummary sont déjà propres, on peut les encoder directement
    return json_encode($response->reservations);
  }
}
