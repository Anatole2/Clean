<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Shared;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;

class HomeController extends AbstractController
{
  public function __construct(
    private PresenterFactory $presenterFactory,
  ) {}

  public function __invoke()
  {
    $response = new \stdClass();
    $presenter = $this->presenterFactory->create('Shared\\Home');

    echo $presenter->present($response);
  }
}
