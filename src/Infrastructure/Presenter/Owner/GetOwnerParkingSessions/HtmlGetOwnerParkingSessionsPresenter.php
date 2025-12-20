<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\GetOwnerParkingSessions;

use App\Infrastructure\Presenter\PresenterInterface;
use Twig\Environment;

class HtmlGetOwnerParkingSessionsPresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}

  public function present($response): string
  {
    return $this->twig->render('owner/parking_sessions.html.twig', [
      'parking' => $response->parking,
      'sessions' => $response->sessions
    ]);
  }
}
