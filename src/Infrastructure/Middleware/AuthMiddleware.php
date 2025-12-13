<?php

namespace App\Infrastructure\Middleware;

use App\Infrastructure\Security\JwtService;
use Exception;

/**
 * Middleware pour vérifier la présence et la validité d'un JWT.
 * Il cherche d'abord dans le Header (API), puis dans les Cookies (Web).
 */
class AuthMiddleware
{
  private JwtService $jwtService;

  public function __construct(JwtService $jwtService)
  {
    $this->jwtService = $jwtService;
  }

  /**
   * Extrait le token, le décode et le valide.
   * @throws Exception Si le token est manquant, invalide ou expiré.
   * @return object Le payload décodé (informations de l'utilisateur).
   */
  public function authenticate(): object
  {
    $token = null;

    // --- ÉTAPE 1 : Chercher dans le Header (Méthode API / Postman / cURL) ---
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!empty($authHeader) && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
      $token = $matches[1];
    }

    // --- ÉTAPE 2 : Si pas trouvé, chercher dans le Cookie (Méthode Navigateur / Formulaire) ---
    if ($token === null) {
      // 'auth_token' est le nom qu'on a donné au cookie dans le navigateur
      $token = $_COOKIE['auth_token'] ?? null;
    }

    // --- ÉTAPE 3 : Vérification finale ---
    if ($token === null) {
      // Important : On met le code 401 pour que index.php puisse rediriger si besoin
      throw new Exception("Utilisateur non authentifié (Token introuvable)", 401);
    }

    // --- ÉTAPE 4 : Décoder et valider ---
    try {
      return $this->jwtService->decodeToken($token);
    } catch (Exception $e) {
      throw new Exception("Accès refusé : " . $e->getMessage(), 401);
    }
  }
}
