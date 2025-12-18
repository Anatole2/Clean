<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Shared\GetParkingDetails;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\Shared\GetParkingDetails\GetParkingDetailsResponse;
use Twig\Environment;

class HtmlGetParkingDetailsPresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}

  public function present(object $response): string
  {
    if (!$response instanceof GetParkingDetailsResponse) {
      throw new \InvalidArgumentException("Mauvais type de réponse.");
    }

    return $this->twig->render('shared/parking_details.html.twig', [
      'parking' => $response->parking
    ]);
  }
}
