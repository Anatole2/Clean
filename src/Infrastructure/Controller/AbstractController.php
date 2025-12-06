<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

abstract class AbstractController
{
  protected function getRequestData(): array
  {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
  }

  protected function sendError(string $message, int $status = 400): void
  {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit;
  }

  /**
   * Récupère l'utilisateur injecté par le Middleware dans index.php
   */
  protected function getAuthUser(): array
  {
    if (!isset($_REQUEST['auth_user'])) {
      throw new \Exception("Utilisateur non authentifié", 401);
    }
    // Conversion de l'objet stdClass (du JWT) en array si nécessaire
    return (array) $_REQUEST['auth_user'];
  }

  protected function getAuthUserId(): string
  {
    $user = $this->getAuthUser();
    // Selon le token de ton collègue, l'ID est dans 'sub' ou 'id'
    return $user['sub'] ?? $user['id'];
  }

  protected function ensureIsOwner(): void
  {
    $user = $this->getAuthUser();
    if (($user['role'] ?? '') !== 'OWNER') {
      throw new \Exception("Accès refusé : Espace Propriétaire uniquement", 403);
    }
  }
}
