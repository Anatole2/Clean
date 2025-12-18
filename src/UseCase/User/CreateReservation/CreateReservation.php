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

    // 2. Calcul du Prix (Via la Grille de Prix du Parking)
    $durationInMinutes = (int) ceil(
      ($request->endTime->getTimestamp() - $request->startTime->getTimestamp()) / 60
    );

    // On utilise la méthode calculatePrice de ton PriceGrid (via le parking)
    $priceInCents = $parking->getPriceGrid()->calculatePrice($durationInMinutes);

    // 3. VÉRIFICATION DE LA CAPACITÉ (Le cœur du métier)
    $occupiedSpots = $this->calculateOccupiedSpots($request);

    // Si (Places Prises + Ma Future Place) > Capacité Totale => ERREUR
    if (($occupiedSpots + 1) > $parking->getTotalPlaces()) {
      throw new Exception("Le parking est complet pour ce créneau.");
    }

    // 4. Création de la réservation
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

  /**
   * Calcule le nombre de places occupées (Réservations + Abonnements)
   */
  private function calculateOccupiedSpots(CreateReservationRequest $request): int
  {
    // A. Compter les réservations ponctuelles (C'est SQL qui fait le travail)
    $reservationCount = $this->reservationRepo->countOverlappingReservations(
      $request->parkingId,
      $request->startTime,
      $request->endTime
    );

    // B. Compter les abonnements actifs
    // 1. On récupère ceux qui chevauchent les DATES (via SQL)
    $potentialSubscriptions = $this->subscriptionRepo->findActiveOverlappingRange(
      $request->parkingId,
      $request->startTime,
      $request->endTime
    );

    $subscriptionCount = 0;
    foreach ($potentialSubscriptions as $sub) {
      // 2. On affine en PHP : Est-ce que l'abonnement mange une place 
      // précisément pendant mes horaires demandés ?
      // On vérifie le début OU la fin pour être sûr d'attraper un chevauchement
      if ($sub->occupiesSpotAt($request->startTime) || $sub->occupiesSpotAt($request->endTime)) {
        $subscriptionCount++;
      }
    }

    return $reservationCount + $subscriptionCount;
  }
}
