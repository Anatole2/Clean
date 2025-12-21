<?php

declare(strict_types=1);

namespace App\UseCase\User\ExitParking;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use DateTimeImmutable;
use Exception;

class ExitParking
{
  private const PENALTY_CENTS = 2000; // 20.00 €

  public function __construct(
    private ParkingSessionRepositoryInterface $sessionRepo,
    private ParkingRepositoryInterface $parkingRepo,
    private ReservationRepositoryInterface $reservationRepo,
    private UserSubscriptionRepositoryInterface $subscriptionRepo
  ) {}

  public function execute(ExitParkingRequest $request): ExitParkingResponse
  {
    $now = new DateTimeImmutable(); // L'heure de sortie réelle

    // 1. Récupérer la session active
    $session = $this->sessionRepo->findActiveByUser($request->userId);
    if (!$session || $session->getParkingId() !== $request->parkingId) {
      throw new Exception("Aucune session active trouvée dans ce parking.");
    }

    // 2. Déterminer l'heure de fin AUTORISÉE
    $allowedEndTime = $this->determineAllowedEndTime($session, $now);

    // 3. Calcul du dépassement
    $overstayMinutes = 0;
    $extraCost = 0;
    $penaltyApplied = false;

    if ($now > $allowedEndTime) {
      // Calcul de la différence en minutes
      $diff = $now->getTimestamp() - $allowedEndTime->getTimestamp();
      $overstayMinutes = (int) ceil($diff / 60);

      // Récupération de la grille tarifaire du parking
      $parking = $this->parkingRepo->findById($request->parkingId);
      if (!$parking) {
        throw new Exception("Parking introuvable.");
      }

      // Calcul du prix du temps supplémentaire selon la grille
      $timeCost = $parking->calculatePrice($overstayMinutes);

      // Ajout de la pénalité forfaitaire
      $extraCost = $timeCost + self::PENALTY_CENTS;
      $penaltyApplied = true;
    }

    // 4. Fermeture de la session
    // Le prix payé dans la session correspond au SURCOÛT (ce qui est dû à la sortie)
    $session->close($now, $extraCost);
    $this->sessionRepo->save($session);

    return new ExitParkingResponse($session, $overstayMinutes, $extraCost, $penaltyApplied);
  }

  private function determineAllowedEndTime(\App\Domain\Entity\ParkingSession $session, DateTimeImmutable $now): DateTimeImmutable
  {
    // Cas A : C'est une Réservation
    if ($session->getReservationId()) {
      $reservation = $this->reservationRepo->findById($session->getReservationId());
      if (!$reservation) {
        // Cas critique : Réservation disparue ? On considère que c'est fini depuis le début de la session (Pénalité max)
        return $session->getEntryTime();
      }
      return $reservation->getEndTime();
    }

    $sub = $this->subscriptionRepo->findActiveForUser($session->getUserId(), $session->getParkingId(), $now);

    if ($sub) {
      return $now->modify('+1 minute');
    }

    return $session->getEntryTime();
  }
}
