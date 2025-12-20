<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\GetParkingReservations;

use App\Infrastructure\Presenter\PresenterInterface;

class JsonGetParkingReservationsPresenter implements PresenterInterface
{
  public function present($response): string
  {
    $data = array_map(fn($r) => [
      'id' => $r->getId(),
      'user_id' => $r->getUserId(),
      'start' => $r->getStartTime()->format('Y-m-d H:i'),
      'end' => $r->getEndTime()->format('Y-m-d H:i'),
      'price' => $r->getPricePaidInCents(),
      'status' => $r->getStatus()
    ], $response->reservations);

    return json_encode(['parking' => $response->parking->getName(), 'reservations' => $data]);
  }
}
