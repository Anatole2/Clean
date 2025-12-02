<?php

declare(strict_types=1);

namespace App\UseCase\Owner\UpdateParkingPrice;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\PriceGrid;
use Exception; // Ou une exception métier personnalisée comme ParkingNotFoundException

class UpdateParkingPrice
{
  public function __construct(
    private ParkingRepositoryInterface $repository
  ) {}

  public function execute(UpdateParkingPriceRequest $request): UpdateParkingPriceResponse
  {
    // 1. Récupération
    $parking = $this->repository->findById($request->parkingId);

    if (!$parking) {
      throw new Exception("Parking introuvable.");
    }

    // 2. Sécurité : Vérification du propriétaire
    if ($parking->getOwnerId() !== $request->ownerId) {
      throw new Exception("Accès refusé : ce parking ne vous appartient pas.");
    }

    // 3. Mise à jour (Validation incluse dans le constructeur de PriceGrid)
    $newPriceGrid = new PriceGrid($request->newPriceGridConfig);
    $parking->changePriceGrid($newPriceGrid);

    // 4. Sauvegarde
    $this->repository->save($parking);

    return UpdateParkingPriceResponse::fromParking($parking);
  }
}
