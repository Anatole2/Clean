<?php

namespace App\Domain\Repository;

use App\Domain\Entity\UserSubscription;
use DateTimeImmutable;

interface UserSubscriptionRepositoryInterface
{
  public function save(UserSubscription $subscription): void;

  /**
   * Récupère tous les abonnements actifs qui sont valides (au niveau des dates)
   * sur la période demandée. 
   * Note : Le filtrage horaire précis (WeeklySchedule) se fera en PHP.
   * * @return UserSubscription[]
   */
  public function findActiveOverlappingRange(
    string $parkingId,
    DateTimeImmutable $start,
    DateTimeImmutable $end
  ): array;

  public function findActiveForUser(string $userId, string $parkingId, \DateTimeImmutable $now): ?\App\Domain\Entity\UserSubscription;
  /**
   * Compte le nombre d'abonnements qui chevauchent la période donnée pour un parking.
   */
  public function countActiveForParking(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): int;

  // Tu auras sans doute besoin de ça plus tard pour l'espace User :
  public function findByUserId(string $userId): array;

  public function countActiveAt(string $parkingId, DateTimeImmutable $time): int;

  /**
   * Calcule la somme des abonnements vendus (débutant) dans la plage donnée.
   */
  public function calculateRevenue(string $parkingId, \DateTimeImmutable $start, \DateTimeImmutable $end): int;
}
