<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\ListOwnerParkings;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\Owner\GetOwnerParkings\GetOwnerParkingsResponse;

class JsonListOwnerParkingsPresenter implements PresenterInterface
{
  public function present(mixed $response): string
  {
    if (!$response instanceof GetOwnerParkingsResponse) {
      throw new \InvalidArgumentException("Mauvais type de réponse");
    }

    // Pour l'API, on renvoie simplement les données brutes en JSON
    return json_encode($response->parkings);
  }
}
