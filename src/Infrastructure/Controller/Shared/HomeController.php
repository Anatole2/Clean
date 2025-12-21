<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Shared;

use App\Infrastructure\Controller\AbstractController;
use Twig\Environment;

class HomeController extends AbstractController
{
  public function __construct(private Environment $twig) {}

  public function __invoke()
  {
    $content = $this->twig->render('index.html.twig');

    echo $content;
  }
}
