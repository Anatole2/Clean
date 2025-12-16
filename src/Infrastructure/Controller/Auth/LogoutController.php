<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Auth;

use App\Infrastructure\Controller\AbstractController;

class LogoutController extends AbstractController
{
  public function __invoke(): void
  {
    // 1. On supprime le cookie en le périmant dans le passé
    setcookie(
      'auth_token',
      '',  // On vide la valeur (double sécurité)
      [
        'expires' => 1, // Force la date au 01/01/1970
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Strict'
      ]
    );

    // 2. Si c'est une API (JSON), on confirme juste
    if ($this->wantsJson()) {
      echo json_encode(['status' => 'success', 'message' => 'Déconnecté']);
      return;
    }

    // 3. Sinon, redirection vers la page de login
    header('Location: /login');
    exit;
  }
}
