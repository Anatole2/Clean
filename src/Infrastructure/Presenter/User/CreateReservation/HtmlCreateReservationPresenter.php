<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\User\CreateReservation;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\User\CreateReservation\CreateReservationResponse;
use Twig\Environment;

class HtmlCreateReservationPresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}

  public function present(object $response): string
  {
    /** @var CreateReservationResponse $response */
    // On affiche une page de confirmation
    return $this->twig->render('user/reservation_success.html.twig', [
      'reservation' => $response->reservation
    ]);
  }
}
