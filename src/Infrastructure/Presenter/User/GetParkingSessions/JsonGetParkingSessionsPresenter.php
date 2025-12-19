<?php

namespace App\Infrastructure\Presenter\User\GetParkingSessions;

use App\Infrastructure\Presenter\PresenterInterface;

class JsonGetParkingSessionsPresenter implements PresenterInterface
{
  public function present($response): string
  {
    return json_encode($response->sessions);
  }
}
