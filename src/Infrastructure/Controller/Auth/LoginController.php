<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Auth;

use App\Infrastructure\Controller\AbstractController;
use App\UseCase\Auth\Login\Login;
use App\UseCase\Auth\Login\LoginRequest;
use Twig\Environment;

class LoginController extends AbstractController
{
  public function __construct(
    private Login $useCase,
    private Environment $twig
  ) {}

  public function __invoke(): void
  {
    // 1. Si c'est du POST, on traite la connexion
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $this->handlePost();
      return;
    }

    // 2. Sinon (GET), on affiche le formulaire
    echo $this->twig->render('auth/login.html.twig');
  }

  private function handlePost(): void
  {
    $input = $this->getRequestData();

    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    try {
      if (empty($email) || empty($password)) {
        throw new \InvalidArgumentException("Veuillez remplir tous les champs.");
      }

      $request = new LoginRequest($email, $password);
      $response = $this->useCase->execute($request);

      $this->setAuthCookie($response->token);

      // Si c'est une API (JSON demandée via cURL/Postman)
      if ($this->wantsJson()) {
        echo json_encode([
          'status' => 'success',
          'message' => 'Connexion réussie',
          'token' => $response->token,
          'user' => [
            'id' => $response->account->getId(),
            'role' => $response->account->getRole()
          ]
        ]);
        return;
      }

      // Redirection Web
      if ($response->account->getRole() === 'OWNER') {
        header('Location: /dashboard');
      } else {
        header('Location: /');
      }
      exit;
    } catch (\Exception $e) {
      $this->handleError($e, $email);
    }
  }

  private function setAuthCookie(string $token): void
  {
    setcookie(
      'auth_token',
      $token,
      [
        'expires' => time() + 3600,
        'path' => '/',
        'domain' => '',
        'secure' => false, // ⚠️ Mettre à TRUE en production (HTTPS)
        'httponly' => true, // Sécurité XSS
        'samesite' => 'Strict'
      ]
    );
  }

  private function handleError(\Exception $e, string $lastEmail): void
  {
    $statusCode = ($e instanceof \InvalidArgumentException) ? 400 : 401;

    if ($this->wantsJson()) {
      http_response_code($statusCode);
      echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
      return;
    }

    // Rendu HTML en cas d'erreur
    echo $this->twig->render('auth/login.html.twig', [
      'error' => $e->getMessage(),
      'last_email' => $lastEmail
    ]);
  }
}
