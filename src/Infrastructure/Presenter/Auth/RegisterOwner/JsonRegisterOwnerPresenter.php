<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Auth\RegisterOwner;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\Auth\RegisterOwner\RegisterOwnerResponse;

class JsonRegisterOwnerPresenter implements PresenterInterface
{
  public function present(mixed $response): string
  {
    if (!$response instanceof RegisterOwnerResponse) {
      throw new \InvalidArgumentException("Mauvais type de réponse");
    }

    return json_encode([
      'status' => 'success',
      'message' => 'Compte propriétaire créé avec succès',
      'data' => [
        'id' => $response->id,
        'email' => $response->email,
        'firstName' => $response->firstName,
        'lastName' => $response->lastName
      ]
    ]);
  }
}
