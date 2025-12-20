<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\GetUnauthorizedParkers;

use App\Infrastructure\Presenter\PresenterInterface;
use Twig\Environment;

class HtmlGetUnauthorizedParkersPresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}

  public function present($response): string
  {
    return $this->twig->render('owner/unauthorized_parkers.html.twig', [
      'parking' => $response->parking,
      'squatters' => $response->squatters // Liste des sessions en infraction
    ]);
  }
}
