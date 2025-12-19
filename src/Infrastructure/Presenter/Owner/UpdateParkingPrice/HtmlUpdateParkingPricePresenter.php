<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\UpdateParkingPrice;

use App\Infrastructure\Presenter\PresenterInterface;
use Twig\Environment;

class HtmlUpdateParkingPricePresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}

  public function present($response): string
  {
    // ICI : On affiche le template "Succès/Récapitulatif" au lieu du formulaire
    return $this->twig->render('owner/update_parking_prices_success.html.twig', [
      'parking' => $response->parking
    ]);
  }
}
