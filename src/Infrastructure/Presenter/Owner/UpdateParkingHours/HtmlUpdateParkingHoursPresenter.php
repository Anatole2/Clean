<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\UpdateParkingHours;

use App\Infrastructure\Presenter\PresenterInterface;
use Twig\Environment;

class HtmlUpdateParkingHoursPresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}

  public function present($response): string
  {
    // On rend le template de succès en lui passant le parking mis à jour
    return $this->twig->render('owner/update_parking_hours_success.html.twig', [
      'parking' => $response->parking
    ]);
  }
}
