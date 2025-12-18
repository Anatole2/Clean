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
}
