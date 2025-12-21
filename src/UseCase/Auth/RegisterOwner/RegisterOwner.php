<?php

declare(strict_types=1);

namespace App\UseCase\Auth\RegisterOwner;

use App\Domain\Entity\Owner;
use App\Domain\Repository\AccountRepositoryInterface;
use App\Domain\Service\IdGeneratorInterface; // Utilise ton RamseyIdGenerator


class RegisterOwner
{
  public function __construct(
    private AccountRepositoryInterface $repository,
    private IdGeneratorInterface $idGenerator
  ) {}

  public function execute(RegisterOwnerRequest $request): RegisterOwnerResponse
  {
    // 1. Vérifier si l'email existe déjà
    if ($this->repository->findByEmail($request->email)) {
      throw new \Exception("Cet email est déjà utilisé.");
    }

    // 2. Créer l'entité Owner (le mot de passe sera haché ici via create)
    $owner = Owner::create(
      $this->idGenerator->generate(),
      $request->email,
      $request->password,
      $request->firstName,
      $request->lastName
    );

    // 3. Sauvegarder
    $this->repository->save($owner);

    return new RegisterOwnerResponse(
      $owner->getId(),
      $owner->getEmail(),
      $owner->getFirstName(),
      $owner->getLastName()
    );
  }
}
