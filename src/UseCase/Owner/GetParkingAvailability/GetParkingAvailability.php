<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetParkingAvailability;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\UseCase\Owner\GetParkingAvailability\GetParkingAvailabilityRequest;
use App\UseCase\Owner\GetParkingAvailability\GetParkingAvailabilityResponse;
use Exception;

class GetParkingAvailability
{
  public function __construct(
    private ParkingRepositoryInterface $parkingRepo,
    private ReservationRepositoryInterface $reservationRepo,
    private UserSubscriptionRepositoryInterface $subscriptionRepo,
    private ParkingSessionRepositoryInterface $sessionRepo
  ) {}

  public function execute(GetParkingAvailabilityRequest $request): GetParkingAvailabilityResponse
  {
    // 1. Récupération & Sécurité
    $parking = $this->parkingRepo->findById($request->parkingId);
    if (!$parking) {
      throw new Exception("Parking introuvable.");
    }
    if ($parking->getOwnerId() !== $request->ownerId) {
      throw new Exception("Accès refusé.");
    }

    // 2. Interrogation des Repositories (Compteurs)
    $nbReservations = $this->reservationRepo->countActiveAt($request->parkingId, $request->checkTime);
    $nbAbonnements  = $this->subscriptionRepo->countActiveAt($request->parkingId, $request->checkTime);
    $nbSquatteurs   = $this->sessionRepo->countOverstayingCars($request->parkingId, $request->checkTime);

    // 3. Calculs
    $occupied = $nbReservations + $nbAbonnements + $nbSquatteurs;

    // On s'assure que le dispo ne descend pas en dessous de 0 (cas de surbooking ou erreur)
    $available = max(0, $parking->getTotalPlaces() - $occupied);

    return new GetParkingAvailabilityResponse(
      $parking,
      $request->checkTime,
      $parking->getTotalPlaces(),
      $nbReservations,
      $nbAbonnements,
      $nbSquatteurs,
      $available
    );
  }
}
