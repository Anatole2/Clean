<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\CreateParking;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\Owner\CreateParking\CreateParkingResponse;

class JsonCreateParkingPresenter implements PresenterInterface
{
  public function present(object $responseDTO): string
  {
    /** @var CreateParkingResponse $responseDTO */
    return json_encode([
      'status' => 'success',
      'data' => [
        'id' => $responseDTO->id,
        'name' => $responseDTO->name,
        'totalPlaces' => $responseDTO->totalPlaces,
        'links' => [
          'self' => "/parkings/{$responseDTO->id}"
        ]
      ]
    ]);
  }
}
