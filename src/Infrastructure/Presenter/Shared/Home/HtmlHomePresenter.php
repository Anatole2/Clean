<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Shared\Home;

use App\Infrastructure\Presenter\PresenterInterface;
use Twig\Environment;

class HtmlHomePresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}

  public function present(object $response): string
  {
    return $this->twig->render('index.html.twig');
  }
}
