<?php

declare(strict_types=1);

namespace App\UseCase\Auth\RegisterUser;

use App\Domain\Entity\User;
use App\Domain\Repository\AccountRepositoryInterface;
use App\Domain\Service\IdGeneratorInterface;

/**
 * Gère l'inscription des utilisateurs "Conducteurs" (Drivers).
 *
 * --- NOTE D'ARCHITECTURE ---
 * Bien que la logique soit actuellement similaire à `RegisterOwner`,
 * ce Use Case est maintenu séparé intentionnellement (S.R.P).
 *
 * Raisons de la séparation :
 * 1. Divergence des données : Les conducteurs pourront à l'avenir fournir des informations
 * spécifiques comme : (Plaque d'immatriculation, Type de véhicule) absentes chez les propriétaires.
 * 2. Divergence des processus : L'inscription conducteur est immédiate, là où celle
 * des propriétaires pourrait nécessiter une validation (KYC) ou des étapes bancaires.
 */

class RegisterUser
{
  public function __construct(
    private AccountRepositoryInterface $repository,
    private IdGeneratorInterface $idGenerator
  ) {}

  public function execute(RegisterUserRequest $request): RegisterUserResponse
  {
    if ($this->repository->findByEmail($request->email)) {
      throw new \Exception("Cet email est déjà utilisé.");
    }

    $user = User::create(
      $this->idGenerator->generate(),
      $request->email,
      $request->password,
      $request->firstName,
      $request->lastName
    );

    $this->repository->save($user);

    return new RegisterUserResponse(
      $user->getId(),
      $user->getEmail(),
      $user->getFirstName(),
      $user->getLastName()
    );
  }
}
