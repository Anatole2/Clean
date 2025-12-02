<?php

declare(strict_types=1);

namespace App\UseCase\Owner\UpdateParkingHours;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\WeeklySchedule;
use Exception;

class UpdateParkingHours
{
  public function __construct(
    private ParkingRepositoryInterface $repository
  ) {}

  public function execute(UpdateParkingHoursRequest $request): UpdateParkingHoursResponse
  {
    $parking = $this->repository->findById($request->parkingId);

    if (!$parking) {
      throw new Exception("Parking introuvable.");
    }

    if ($parking->getOwnerId() !== $request->ownerId) {
      throw new Exception("Accès refusé.");
    }

    // Création du Value Object (Validation incluse)
    $newSchedule = new WeeklySchedule($request->newOpeningHoursConfig);

    // Modification de l'entité
    $parking->changeOpeningHours($newSchedule);

    // Sauvegarde
    $this->repository->save($parking);

    return UpdateParkingHoursResponse::fromParking($parking);
  }
}
