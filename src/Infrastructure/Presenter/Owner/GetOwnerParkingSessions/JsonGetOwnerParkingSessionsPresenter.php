<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\GetOwnerParkingSessions;

use App\Infrastructure\Presenter\PresenterInterface;

class JsonGetOwnerParkingSessionsPresenter implements PresenterInterface
{
  public function present($response): string
  {
    $data = array_map(fn($s) => [
      'id' => $s->getId(),
      'user' => $s->getUserId(),
      'entry' => $s->getEntryTime()->format('Y-m-d H:i:s'),
      'exit' => $s->getExitTime()?->format('Y-m-d H:i:s'), // Nullable
      'is_ongoing' => $s->isOngoing(),
      'price' => $s->getPricePaid()
    ], $response->sessions);

    return json_encode(['parking' => $response->parking->getName(), 'sessions' => $data]);
  }
}
