<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\GetParkingReservations;

use App\Infrastructure\Presenter\PresenterInterface;
use Twig\Environment;

class HtmlGetParkingReservationsPresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}

  public function present($response): string
  {
    return $this->twig->render('owner/parking_reservations.html.twig', [
      'parking' => $response->parking,
      'reservations' => $response->reservations
    ]);
  }
}
