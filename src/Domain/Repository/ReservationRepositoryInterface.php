<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Reservation;
use DateTimeImmutable;

interface ReservationRepositoryInterface
{
  public function save(Reservation $reservation): void;

  /**
   * Compte combien de réservations ponctuelles chevauchent cet intervalle.
   */
  public function countOverlappingReservations(
    string $parkingId,
    DateTimeImmutable $start,
    DateTimeImmutable $end
  ): int;

  public function findByUserId(string $userId): array;

  public function findActiveForUser(string $userId, string $parkingId, \DateTimeImmutable $now): ?\App\Domain\Entity\Reservation;

  public function findById(string $id): ?\App\Domain\Entity\Reservation;
}
