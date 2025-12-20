<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetUnauthorizedParkers;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\UseCase\Owner\GetUnauthorizedParkers\GetUnauthorizedParkersRequest;
use App\UseCase\Owner\GetUnauthorizedParkers\GetUnauthorizedParkersResponse;
use DateTimeImmutable;
use Exception;

class GetUnauthorizedParkers
{
  public function __construct(
    private ParkingRepositoryInterface $parkingRepo,
    private ParkingSessionRepositoryInterface $sessionRepo,
    private ReservationRepositoryInterface $resRepo,
    private UserSubscriptionRepositoryInterface $subRepo
  ) {}

  public function execute(GetUnauthorizedParkersRequest $request): GetUnauthorizedParkersResponse
  {
    // 1. Vérif Parking & Owner
    $parking = $this->parkingRepo->findById($request->parkingId);
    if (!$parking) throw new Exception("Parking introuvable.");
    if ($parking->getOwnerId() !== $request->ownerId) throw new Exception("Accès refusé.");

    // 2. Récupérer tous les véhicules présents
    $activeSessions = $this->sessionRepo->findActiveSessionsByParkingId($request->parkingId);

    $squatters = [];
    $now = new DateTimeImmutable();

    foreach ($activeSessions as $session) {
      $isAuthorized = false;

      // A. Vérifier la réservation liée (si elle existe)
      if ($session->getReservationId()) {
        $reservation = $this->resRepo->findById($session->getReservationId());
        // Si la réservation existe et n'est pas encore finie
        if ($reservation && $reservation->getEndTime() > $now) {
          $isAuthorized = true;
        }
      }

      // B. Si pas autorisé par réservation, vérifier l'abonnement
      if (!$isAuthorized) {
        // On cherche un abonnement actif pour ce user, ce parking, maintenant
        $sub = $this->subRepo->findActiveForUser($session->getUserId(), $request->parkingId, $now);
        if ($sub) {
          $isAuthorized = true;
        }
      }

      // C. Si toujours pas autorisé -> C'est un squatteur
      if (!$isAuthorized) {
        $squatters[] = $session;
      }
    }

    return new GetUnauthorizedParkersResponse($parking, $squatters);
  }
}
