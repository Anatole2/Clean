<?php

namespace App\Infrastructure\Middleware;

use App\Infrastructure\Security\JwtService;
use Exception;

/**
 * Middleware pour vérifier la présence et la validité d'un JWT dans les headers.
 * S'exécute avant les contrôleurs des routes protégées.
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
     * * @throws Exception Si le token est manquant, invalide ou expiré.
     * @return object Le payload décodé (informations de l'utilisateur).
     */
    public function authenticate(): object
    {
        // 1. Récupérer le header Authorization
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            throw new Exception("Accès refusé : Token manquant ou format incorrect.");
        }

        $token = $matches[1];

        // 2. Décoder et valider le token
        try {
            $payload = $this->jwtService->decodeToken($token);
            return $payload; 
            
        } catch (Exception $e) {
            throw new Exception("Accès refusé : " . $e->getMessage());
        }
    }
}