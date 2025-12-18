<?php

declare(strict_types=1);

namespace App\UseCase\User\CreateReservation;

use App\Domain\Entity\Reservation;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\Infrastructure\Service\RamseyIdGenerator;
use Exception;

class CreateReservation
{
  public function __construct(
    private ParkingRepositoryInterface $parkingRepo,
    private ReservationRepositoryInterface $reservationRepo,
    private UserSubscriptionRepositoryInterface $subscriptionRepo,
    private RamseyIdGenerator $idGenerator
  ) {}

  public function execute(CreateReservationRequest $request): CreateReservationResponse
  {
    // 1. Récupération du Parking
    $parking = $this->parkingRepo->findById($request->parkingId);
    if (!$parking) {
      throw new Exception("Parking introuvable.");
    }

    // --- 🆕 AJOUT : VÉRIFICATION DES HORAIRES D'OUVERTURE ---
    $openingHours = $parking->getOpeningHours(); // Assure-toi que ce getter existe dans Parking

    // On vérifie que le parking est ouvert au moment de l'arrivée ET au moment du départ
    if (!$openingHours->isOpen($request->startTime) || !$openingHours->isOpen($request->endTime)) {
      throw new Exception("Le parking est fermé sur les horaires demandés.");
    }
    // --------------------------------------------------------

    // 2. Calcul du Prix
    $durationInMinutes = (int) ceil(
      ($request->endTime->getTimestamp() - $request->startTime->getTimestamp()) / 60
    );

    $priceInCents = $parking->getPriceGrid()->calculatePrice($durationInMinutes);

    // 3. VÉRIFICATION DE LA CAPACITÉ
    $occupiedSpots = $this->calculateOccupiedSpots($request);

    if (($occupiedSpots + 1) > $parking->getTotalPlaces()) {
      throw new Exception("Le parking est complet pour ce créneau.");
    }

    // 4. Création
    $reservation = new Reservation(
      $this->idGenerator->generate(),
      $request->userId,
      $request->parkingId,
      $request->startTime,
      $request->endTime,
      $priceInCents
    );

    // 5. Sauvegarde
    $this->reservationRepo->save($reservation);

    return new CreateReservationResponse($reservation);
  }

  // ... (Ta méthode calculateOccupiedSpots reste inchangée)
  private function calculateOccupiedSpots(CreateReservationRequest $request): int
  {
    // ...
    $reservationCount = $this->reservationRepo->countOverlappingReservations(
      $request->parkingId,
      $request->startTime,
      $request->endTime
    );
    $potentialSubscriptions = $this->subscriptionRepo->findActiveOverlappingRange(
      $request->parkingId,
      $request->startTime,
      $request->endTime
    );

    $subscriptionCount = 0;
    foreach ($potentialSubscriptions as $sub) {
      if ($sub->occupiesSpotAt($request->startTime) || $sub->occupiesSpotAt($request->endTime)) {
        $subscriptionCount++;
      }
    }
    return $reservationCount + $subscriptionCount;
  }
}
