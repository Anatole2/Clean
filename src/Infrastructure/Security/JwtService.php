<?php

namespace App\Infrastructure\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use DateTimeImmutable;
use App\Domain\Entity\Account;

class JwtService
{
    private string $secretKey;
    private string $algorithm = 'HS256';
    private int $expirationSeconds = 3600;

    public function __construct(string $secretKey)
    {
        // La clé secrète doit être injectée depuis un fichier de configuration/env.
        $this->secretKey = $secretKey;
    }

    /**
     * Génère un JWT pour un compte donné.
     * * @param Account $account L'entité Account (User ou Owner)
     * @return string Le JWT encodé
     */
    public function generateToken(Account $account): string
    {
        $now = new DateTimeImmutable();
        
        $payload = [
            'iat' => $now->getTimestamp(), // Issued at: Heure de création du token
            'exp' => $now->modify(sprintf('+%d seconds', $this->expirationSeconds))->getTimestamp(), // Expiration
            'iss' => 'shared-parking-app', // Issuer: Émetteur (votre application)
            'sub' => $account->getId(), // Subject: ID du compte (User ou Owner)
            'role' => $account->getRole(), // Rôle (DRIVER ou OWNER)
            'email' => $account->getEmail(),
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Décode et valide un JWT.
     * * @param string $token Le JWT à décoder.
     * @return object Le payload décodé si valide.
     * @throws \Exception Si le token est invalide ou expiré.
     */
    public function decodeToken(string $token): object
    {
        try {
            // Décodage avec la vérification de la signature et des revendications (exp, iat, etc.)
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return $decoded;
            
        } catch (\Firebase\JWT\ExpiredException $e) {
            // Le token est expiré
            throw new \Exception("Token expiré.");
        } catch (\Exception $e) {
            // Autres erreurs (signature invalide, etc.)
            throw new \Exception("Token invalide: " . $e->getMessage());
        }
    }
}