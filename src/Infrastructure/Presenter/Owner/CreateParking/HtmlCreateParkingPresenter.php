<?php

namespace App\Infrastructure\Presenter\Owner\CreateParking;

use App\Infrastructure\Presenter\PresenterInterface;
use Twig\Environment;

class HtmlCreateParkingPresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}

  public function present(object $responseDTO): string
  {
    // On affiche une page de confirmation
    return $this->twig->render('owner/parking_created.html.twig', [
      'parking' => $responseDTO
    ]);
  }
}
