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
}
