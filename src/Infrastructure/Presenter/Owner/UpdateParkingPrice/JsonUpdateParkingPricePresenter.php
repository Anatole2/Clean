<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\UpdateParkingPrice;

use App\Infrastructure\Presenter\PresenterInterface;

class JsonUpdateParkingPricePresenter implements PresenterInterface
{
  public function present($response): string
  {
    return json_encode([
      'status' => 'success',
      'message' => 'Tarifs mis à jour avec succès.',
      'parking_id' => $response->parking->getId(),
      'new_prices' => $response->parking->getPriceGrid()->toArray()
    ]);
  }
}
