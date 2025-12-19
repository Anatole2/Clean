<?php

namespace App\Infrastructure\Presenter\User\GetParkingSessions;

use App\Infrastructure\Presenter\PresenterInterface;
use Twig\Environment;

class HtmlGetParkingSessionsPresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}
  public function present($response): string
  {
    return $this->twig->render('user/my_parking_sessions.html.twig', ['sessions' => $response->sessions]);
  }
}
