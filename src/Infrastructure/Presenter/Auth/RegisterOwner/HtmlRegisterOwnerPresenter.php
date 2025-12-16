<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Auth\RegisterOwner;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\Auth\RegisterOwner\RegisterOwnerResponse;
use Twig\Environment;

class HtmlRegisterOwnerPresenter implements PresenterInterface
{
  public function __construct(
    private Environment $twig
  ) {}

  public function present(mixed $response): string
  {
    if (!$response instanceof RegisterOwnerResponse) {
      throw new \InvalidArgumentException("Mauvais type de réponse");
    }

    // Option A : On redirige via un header PHP (c'est un side-effect, mais souvent accepté dans le Presenter HTML)
    // header('Location: /login?registered=1');
    // return '';

    // Option B (Plus "Pure") : On affiche une page de confirmation
    return $this->twig->render('auth/register_success.html.twig', [
      'name' => $response->firstName
    ]);
  }
}
