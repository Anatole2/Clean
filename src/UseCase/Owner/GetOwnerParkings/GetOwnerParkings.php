<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetOwnerParkings;

use App\Domain\Repository\ParkingRepositoryInterface;

class GetOwnerParkings
{
  public function __construct(

    private ParkingRepositoryInterface $repository,

  ) {}

  public function execute(GetOwnerParkingsRequest $request): GetOwnerParkingsResponse
  {
    // 1. Récupération des entités Parking depuis la BDD
    $parkings = $this->repository->findByOwnerId($request->ownerId);

    // 2. Transformation (Mapping) Domaine -> Données simples
    $data = array_map(function ($parking) {
      $coords = $parking->getCoordinates();
      return [
        'id' => $parking->getId(),
        'name' => $parking->getName(),
        'totalPlaces' => $parking->getTotalPlaces(),
        'latitude' => $coords->getLatitude(),
        'longitude' => $coords->getLongitude()
      ];
    }, $parkings);

    // 3. Retourne la réponse
    return new GetOwnerParkingsResponse($data);
  }
}
