<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

abstract class AbstractController
{
  /**
   * Récupère les données de la requête, qu'elles viennent de JSON ou de $_POST.
   */
  protected function getRequestData(): array
  {
    // 1. Essayer de lire le JSON (API / Fetch / cURL)
    $input = file_get_contents('php://input');
    $jsonData = json_decode($input, true);

    if (!is_array($jsonData)) {
      $jsonData = [];
    }

    // 2. Fusionner avec $_POST (Formulaires HTML classiques)
    // Note : On fusionne les deux pour être sûr de tout attraper.
    return array_merge($_POST, $jsonData);
  }

  /**
   * Détermine si le client attend une réponse JSON.
   * Utile pour savoir si on doit faire un `echo json_encode` ou un `render` Twig.
   */
  protected function wantsJson(): bool
  {
    return isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
  }

  /**
   * Envoie une réponse JSON d'erreur et arrête le script.
   * (Principalement utilisé pour les contrôleurs purement API)
   */
  protected function sendError(string $message, int $status = 400): void
  {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode([
      'status' => 'error',
      'message' => $message
    ]);
    exit;
  }

  /**
   * Récupère l'utilisateur injecté par le Middleware dans index.php
   * On s'attend à ce que le Middleware ait fait : $_REQUEST['auth_user'] = $decodedToken;
   */
  protected function getAuthUser(): object
  {
    if (!isset($_REQUEST['auth_user'])) {
      throw new \Exception("Utilisateur non authentifié", 401);
    }

    // On retourne l'objet directement (plus pratique que le cast en array)
    // car ton JwtService retourne maintenant un objet stdClass.
    return (object) $_REQUEST['auth_user'];
  }

  protected function getAuthUserId(): string
  {
    $user = $this->getAuthUser();
    // Grâce à ton JwtService amélioré, on est sûrs d'avoir 'id'.
    // On garde le fallback 'sub' au cas où.
    return $user->id ?? $user->sub ?? '';
  }

  protected function ensureIsOwner(): void
  {
    $user = $this->getAuthUser();
    if (($user->role ?? '') !== 'OWNER') {
      throw new \Exception("Accès refusé : Espace Propriétaire uniquement", 403);
    }
  }
  protected function ensureIsUser(): void
  {
    $user = $this->getAuthUser();

    // 1. Cas : Utilisateur non connecté -> On redirige vers le login
    if (!$user) {
      // Si c'est une requête API (cURL/Fetch), on renvoie une 401
      if ($this->wantsJson()) {
        throw new \Exception("Authentification requise", 401);
      }

      // Si c'est un navigateur, on redirige
      header('Location: /login');
      exit;
    }

    // 2. Cas : Utilisateur connecté mais mauvais rôle -> Erreur 403
    if ($user->role !== 'USER') {
      throw new \Exception("Accès refusé : Espace Conducteur uniquement", 403);
    }
  }
}
