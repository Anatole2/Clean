<?php

declare(strict_types=1);

namespace App\UseCase\User\EnterParking;

use App\Domain\Entity\ParkingSession;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\Infrastructure\Service\RamseyIdGenerator;
use DateTimeImmutable;
use Exception;

class EnterParking
{
  public function __construct(
    private ParkingSessionRepositoryInterface $sessionRepo,
    private ReservationRepositoryInterface $reservationRepo,
    private UserSubscriptionRepositoryInterface $subscriptionRepo,
    private RamseyIdGenerator $idGenerator
  ) {}

  public function execute(EnterParkingRequest $request): EnterParkingResponse
  {
    // 1. Vérifier si l'utilisateur n'est pas déjà dedans !
    $existingSession = $this->sessionRepo->findActiveByUser($request->userId);
    if ($existingSession) {
      throw new Exception("Vous êtes déjà stationné dans un parking.");
    }

    $now = new DateTimeImmutable();

    // 2. Chercher une Réservation Active
    $reservation = $this->reservationRepo->findActiveForUser($request->userId, $request->parkingId, $now);

    // 3. Sinon, chercher un Abonnement Actif
    $subscription = null;
    if (!$reservation) {
      $subscription = $this->subscriptionRepo->findActiveForUser($request->userId, $request->parkingId, $now);
    }

    // 4. Si aucun des deux => Refus d'entrée
    if (!$reservation && !$subscription) {
      throw new Exception("Accès refusé : Aucune réservation ou abonnement valide pour ce créneau.");
    }

    // 5. Création de la Session (La barrière s'ouvre)
    $session = new ParkingSession(
      $this->idGenerator->generate(),
      $request->parkingId,
      $request->userId,
      $reservation?->getId(),
      $now,
      null,
      0
    );

    $this->sessionRepo->save($session);

    return new EnterParkingResponse($session);
  }
}
