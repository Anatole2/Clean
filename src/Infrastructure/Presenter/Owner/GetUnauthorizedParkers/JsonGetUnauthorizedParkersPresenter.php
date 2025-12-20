<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\GetUnauthorizedParkers;

use App\Infrastructure\Presenter\PresenterInterface;

class JsonGetUnauthorizedParkersPresenter implements PresenterInterface
{
  public function present($response): string
  {
    $squatters = array_map(fn($s) => [
      'session_id' => $s->getId(),
      'user_id' => $s->getUserId(),
      'entry_time' => $s->getEntryTime()->format(\DateTime::ATOM),
      // Calcul de la durée de squat
      'duration_minutes' => (new \DateTime())->diff($s->getEntryTime())->i
        + ((new \DateTime())->diff($s->getEntryTime())->h * 60)
    ], $response->squatters);

    return json_encode([
      'parking' => $response->parking->getName(),
      'squatters_count' => count($squatters),
      'squatters' => $squatters
    ]);
  }
}
