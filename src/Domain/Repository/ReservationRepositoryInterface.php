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

  /** @return Reservation[] */
  public function findByParkingId(string $parkingId): array;

  public function countActiveAt(string $parkingId, \DateTimeImmutable $time): int;

  /**
   * Calcule la somme des prix payés pour les réservations terminées dans la plage donnée.
   */
  public function calculateRevenue(string $parkingId, \DateTimeImmutable $start, \DateTimeImmutable $end): int;
}
