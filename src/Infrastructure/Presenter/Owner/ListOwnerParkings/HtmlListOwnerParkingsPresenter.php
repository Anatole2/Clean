<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\ListOwnerParkings;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\Owner\GetOwnerParkings\GetOwnerParkingsResponse;
use Twig\Environment;

class HtmlListOwnerParkingsPresenter implements PresenterInterface
{
  public function __construct(
    private Environment $twig
  ) {}

  public function present(mixed $response): string
  {
    if (!$response instanceof GetOwnerParkingsResponse) {
      throw new \InvalidArgumentException("Mauvais type de réponse");
    }

    return $this->twig->render('owner/dashboard.html.twig', [
      'parkings' => $response->parkings
    ]);
  }
}
