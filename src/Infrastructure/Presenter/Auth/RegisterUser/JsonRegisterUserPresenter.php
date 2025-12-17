<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Auth\RegisterUser;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\Auth\RegisterUser\RegisterUserResponse;

class JsonRegisterUserPresenter implements PresenterInterface
{
  public function present(mixed $response): string
  {
    if (!$response instanceof RegisterUserResponse) {
      throw new \InvalidArgumentException("Mauvais type de réponse");
    }

    return json_encode([
      'status' => 'success',
      'message' => 'Compte utilisateur créé avec succès',
      'data' => [
        'id' => $response->id,
        'email' => $response->email,
        'firstName' => $response->firstName,
        'lastName' => $response->lastName
      ]
    ]);
  }
}
