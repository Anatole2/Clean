<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Auth;

use App\Infrastructure\Controller\AbstractController;
use Twig\Environment;

class ShowRegisterOwnerController extends AbstractController
{
  public function __construct(private Environment $twig) {}

  public function __invoke(): void
  {
    // On affiche le formulaire
    echo $this->twig->render('auth/register_owner.html.twig');
  }
}
