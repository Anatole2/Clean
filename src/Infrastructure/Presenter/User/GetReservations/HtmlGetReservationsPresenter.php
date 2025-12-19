<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\User\GetReservations;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\User\GetReservations\GetUserReservationsResponse;
use Twig\Environment;

class HtmlGetReservationsPresenter implements PresenterInterface
{
  public function __construct(
    private Environment $twig
  ) {}

  /**
   * @param GetUserReservationsResponse $response
   */
  public function present($response): string
  {
    return $this->twig->render('user/my_reservations.html.twig', [
      'reservations' => $response->reservations
    ]);
  }
}
