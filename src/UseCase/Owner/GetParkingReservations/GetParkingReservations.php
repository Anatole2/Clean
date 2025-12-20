<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetParkingReservations;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\UseCase\Owner\GetParkingReservations\GetParkingReservationsRequest;
use App\UseCase\Owner\GetParkingReservations\GetParkingReservationsResponse;
use Exception;

class GetParkingReservations
{
  public function __construct(
    private ReservationRepositoryInterface $reservationRepository,
    private ParkingRepositoryInterface $parkingRepository
  ) {}

  public function execute(GetParkingReservationsRequest $request): GetParkingReservationsResponse
  {
    // 1. On récupère le parking pour vérifier le Owner
    $parking = $this->parkingRepository->findById($request->parkingId);

    if (!$parking) {
      throw new Exception("Parking introuvable.");
    }

    if ($parking->getOwnerId() !== $request->ownerId) {
      throw new Exception("Accès refusé.");
    }

    // 2. On récupère les réservations
    $reservations = $this->reservationRepository->findByParkingId($request->parkingId);

    return new GetParkingReservationsResponse($parking, $reservations);
  }
}
