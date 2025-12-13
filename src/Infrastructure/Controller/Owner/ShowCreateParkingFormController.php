<?php

namespace App\Infrastructure\Controller\Owner;

use App\Infrastructure\Controller\AbstractController;
use Twig\Environment;

class ShowCreateParkingFormController extends AbstractController
{
  public function __construct(private Environment $twig) {}

  public function __invoke(): void
  {
    echo $this->twig->render('owner/create_parking.html.twig');
  }
}
