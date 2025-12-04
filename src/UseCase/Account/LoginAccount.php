<?php

namespace App\UseCase\Account;

use App\Domain\Repository\AccountRepositoryInterface;
use App\Infrastructure\Security\JwtService;
use Exception;

class LoginAccount
{
    private AccountRepositoryInterface $accountRepository;
    private JwtService $jwtService;

    public function __construct(AccountRepositoryInterface $accountRepository, JwtService $jwtService)
    {
        $this->accountRepository = $accountRepository;
        $this->jwtService = $jwtService;
    }

    /**
     * Exécute la tentative de connexion : vérifie les identifiants et génère un jeton JWT.
     * * @param string $email
     * @param string $password
     * @return array Contient le token et les données du compte.
     * @throws Exception En cas d'échec de l'authentification.
     */
    public function execute(string $email, string $password): array
    {
        $account = $this->accountRepository->findByEmail($email);

        if (!$account) {
            throw new Exception("Invalid credentials.");
        }

        if (!$account->verifyPassword($password)) {
            throw new Exception("Invalid credentials.");
        }

        $token = $this->jwtService->generateToken($account);

        return [
            'token' => $token,
            'user_id' => $account->getId(),
            'role' => $account->getRole(),
            'first_name' => $account->getFirstName(),
        ];
    }
}