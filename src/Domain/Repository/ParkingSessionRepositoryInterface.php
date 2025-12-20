<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\ParkingSession;

interface ParkingSessionRepositoryInterface
{
  public function save(ParkingSession $session): void;

  // Trouve la session active d'un utilisateur (s'il est déjà dans un parking)
  public function findActiveByUser(string $userId): ?ParkingSession;

  // Trouve une session spécifique par ID
  public function findById(string $id): ?ParkingSession;

  /**
   * Compte les "Squatteurs" : 
   * Les voitures encore présentes (exit_time IS NULL)
   * ALORS QUE leur réservation est terminée (end_time < now).
   * * C'est crucial pour le calcul de capacité.
   */
  public function countOverstayingCars(string $parkingId, \DateTimeImmutable $checkTime): int;

  /**
   * Récupère tout l'historique des sessions (actives et terminées) d'un user.
   * @return ParkingSession[]
   */
  public function findByUserId(string $userId): array;

  /** * Récupère toutes les sessions (en cours et terminées) d'un parking
   * @return ParkingSession[] 
   */
  public function findByParkingId(string $parkingId): array;
}
