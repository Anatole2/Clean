<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Auth;

use App\Infrastructure\Controller\AbstractController;
use Twig\Environment;

class ShowRegisterUserController extends AbstractController
{
  public function __construct(private Environment $twig) {}

  public function __invoke(): void
  {
    echo $this->twig->render('auth/register_user.html.twig');
  }
}
