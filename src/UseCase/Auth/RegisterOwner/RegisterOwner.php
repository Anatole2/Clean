<?php

declare(strict_types=1);

namespace App\UseCase\Auth\RegisterOwner;

use App\Domain\Entity\Owner;
use App\Domain\Repository\AccountRepositoryInterface;
use App\Domain\Service\IdGeneratorInterface; // Utilise ton RamseyIdGenerator

/**
 * Gère l'inscription des Propriétaires de parking (Owners).
 *
 * --- NOTE D'ARCHITECTURE ---
 * Séparation explicite vis-à-vis de `RegisterUser` pour anticiper l'évolution du métier.
 *
 * Les propriétaires peuvent à l'avenir avoir des contraintes spécifiques comme :
 * - Ajout de données financières (IBAN) pour les versements.
 * - Ajout de données légales (SIRET, Identité) pour la conformité.
 * - Acceptation de CGV spécifiques "Vendeurs".
 *
 * Cette séparation évite de coupler la logique d'inscription "Client" et "Fournisseur".
 */


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
