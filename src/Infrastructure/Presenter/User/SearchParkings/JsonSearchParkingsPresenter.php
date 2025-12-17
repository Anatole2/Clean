<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\User\SearchParkings;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\User\SearchParkings\SearchParkingsResponse;

class JsonSearchParkingsPresenter implements PresenterInterface
{
  public function present(object $response): string
  {
    /** @var SearchParkingsResponse $response */

    // Conversion des DTOs en tableaux
    $data = array_map(fn($r) => [
      'id' => $r->id,
      'name' => $r->name,
      'location' => ['lat' => $r->latitude, 'lon' => $r->longitude],
      'places' => $r->totalPlaces,
      'price' => $r->priceLabel
    ], $response->parkings);

    return json_encode([
      'status' => 'success',
      'count' => count($data),
      'results' => $data
    ]);
  }
}
