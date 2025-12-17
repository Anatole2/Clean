<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Auth\RegisterUser;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\Auth\RegisterUser\RegisterUserResponse;
use Twig\Environment;

class HtmlRegisterUserPresenter implements PresenterInterface
{
  public function __construct(
    private Environment $twig
  ) {}

  public function present(mixed $response): string
  {
    if (!$response instanceof RegisterUserResponse) {
      throw new \InvalidArgumentException("Mauvais type de réponse");
    }

    return $this->twig->render('auth/register_success.html.twig', [
      'name' => $response->firstName
    ]);
  }
}
