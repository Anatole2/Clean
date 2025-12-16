<?php

declare(strict_types=1);

namespace App\UseCase\Auth\Login;

use App\Domain\Repository\AccountRepositoryInterface;
use App\Infrastructure\Security\JwtService;

class Login
{
  public function __construct(
    private AccountRepositoryInterface $repository,
    private JwtService $jwtService
  ) {}

  public function execute(LoginRequest $request): LoginResponse
  {
    // 1. Chercher l'utilisateur par email
    $account = $this->repository->findByEmail($request->email);

    // 2. Vérifier le mot de passe
    // On vérifie $account d'abord pour éviter une erreur sur null
    if (!$account || !$account->verifyPassword($request->password)) {
      throw new \Exception("Email ou mot de passe incorrect.");
    }

    // 3. Générer le token via ton nouveau service
    $token = $this->jwtService->generateToken($account);

    return new LoginResponse($token, $account);
  }
}
