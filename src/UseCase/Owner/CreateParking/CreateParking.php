<?php

declare(strict_types=1);

namespace App\UseCase\Owner\CreateParking;

use App\Domain\Entity\Parking;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Service\IdGeneratorInterface;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;

class CreateParking
{
  public function __construct(
    private ParkingRepositoryInterface $repository,
    private IdGeneratorInterface $idGenerator
  ) {}

  public function execute(CreateParkingRequest $request): CreateParkingResponse
  {
    // 1. Instanciation des Value Objects
    $coordinates = new GpsCoordinates($request->latitude, $request->longitude);
    $priceGrid = new PriceGrid($request->priceGridConfig);
    $openingHours = new WeeklySchedule($request->openingHoursConfig);

    // 2. Génération de l'ID technique
    $id = $this->idGenerator->generate();

    // 3. Création de l'Entité Parking
    $parking = new Parking(
      $id,
      $request->ownerId,
      $request->name,
      $coordinates,
      $request->totalPlaces,
      $priceGrid,
      $openingHours
    );

    // 4. Persistance via le Repository (Contrat)
    $this->repository->save($parking);

    // 5. Retour sous forme de DTO de réponse
    return CreateParkingResponse::fromParking($parking);
  }
}
