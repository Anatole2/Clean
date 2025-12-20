<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\UpdateParkingHours;

use App\Infrastructure\Presenter\PresenterInterface;

class JsonUpdateParkingHoursPresenter implements PresenterInterface
{
  public function present($response): string
  {
    return json_encode([
      'status' => 'success',
      'message' => 'Les horaires d\'ouverture ont été mis à jour avec succès.',
      'parking_id' => $response->parking->getId(),
      'new_hours' => $response->parking->getOpeningHours()->toArray()
    ]);
  }
}
