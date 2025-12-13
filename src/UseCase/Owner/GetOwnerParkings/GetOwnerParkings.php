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
    // On ne veut pas que le contrôleur manipule directement l'objet Parking
    $data = array_map(function ($parking) {
      $coords = $parking->getCoordinates();
      return [
        'id' => $parking->getId(), // On suppose que tu as un getter getId()
        'name' => $parking->getName(),
        // Ajoute ici les champs que tu veux afficher dans le tableau
        'totalPlaces' => $parking->getTotalPlaces(),

        // Exemple pour l'adresse ou la ville si tu l'as implémenté, 
        // sinon tu peux mettre une chaîne vide pour l'instant
        'latitude' => $coords->getLatitude(),
        'longitude' => $coords->getLongitude()
      ];
    }, $parkings);

    // 3. Retourne la réponse
    return new GetOwnerParkingsResponse($data);
  }
}
