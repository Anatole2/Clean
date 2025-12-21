<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Shared\Home;

use App\Infrastructure\Presenter\PresenterInterface;

class JsonHomePresenter implements PresenterInterface
{
  public function present(object $response): string
  {
    return json_encode(['message' => 'Welcome to the shared Parking API']);
  }
}
