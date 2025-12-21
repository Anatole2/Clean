<?php

declare(strict_types=1);

namespace App\UseCase\User\SearchParkings;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\Entity\Parking;

class SearchParkings
{
  public function __construct(
    private ParkingRepositoryInterface $repository
  ) {}

  public function execute(SearchParkingsRequest $request): SearchParkingsResponse
  {
    // 1. Création du VO
    $center = new GpsCoordinates($request->latitude, $request->longitude);

    // 2. Récupération des entités
    $parkings = $this->repository->findNearby($center, $request->radiusInKm);

    // 3. Transformation en DTOs
    $results = array_map(function (Parking $parking) {
      return new SearchParkingsResult(
        $parking->getId(),
        $parking->getName(),
        $parking->getCoordinates()->getLatitude(),
        $parking->getCoordinates()->getLongitude(),
        $parking->getTotalPlaces(),
        $this->generatePriceLabel($parking)
      );
    }, $parkings);

    return new SearchParkingsResponse($results);
  }

  /**
   * Génère une étiquette de prix standardisée (Tarif pour 1h).
   */
  private function generatePriceLabel(Parking $parking): string
  {
    $grid = $parking->getPriceGrid();

    $priceInCents = $grid->calculatePrice(60);

    if ($priceInCents === 0) {
      return "Gratuit 1h";
    }

    $priceInEuros = $priceInCents / 100;

    return number_format($priceInEuros, 2) . ' € / 1h';
  }
}
