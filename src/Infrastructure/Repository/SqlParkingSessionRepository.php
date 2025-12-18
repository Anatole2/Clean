<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\ParkingSession;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use DateTimeImmutable;
use PDO;

class SqlParkingSessionRepository implements ParkingSessionRepositoryInterface
{
  public function __construct(private PDO $pdo) {}

  public function save(ParkingSession $session): void
  {
    $stmt = $this->pdo->prepare("
            INSERT INTO parking_sessions (id, parking_id, user_id, reservation_id, entry_time, exit_time, price_paid)
            VALUES (:id, :parking_id, :user_id, :reservation_id, :entry_time, :exit_time, :price_paid)
            ON DUPLICATE KEY UPDATE
                exit_time = VALUES(exit_time),
                price_paid = VALUES(price_paid)
        ");

    $stmt->execute([
      'id' => $session->getId(),
      'parking_id' => $session->getParkingId(),
      'user_id' => $session->getUserId(),
      'reservation_id' => $session->getReservationId(),
      'entry_time' => $session->getEntryTime()->format('Y-m-d H:i:s'),
      'exit_time' => $session->getExitTime()?->format('Y-m-d H:i:s'),
      'price_paid' => $session->getPricePaid()
    ]);
  }

  public function findActiveByUser(string $userId): ?ParkingSession
  {
    $stmt = $this->pdo->prepare("
            SELECT * FROM parking_sessions 
            WHERE user_id = :user_id AND exit_time IS NULL 
            LIMIT 1
        ");
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->hydrate($row) : null;
  }

  public function findById(string $id): ?ParkingSession
  {
    $stmt = $this->pdo->prepare("SELECT * FROM parking_sessions WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->hydrate($row) : null;
  }

  public function countOverstayingCars(string $parkingId, DateTimeImmutable $checkTime): int
  {
    // On cherche les sessions actives (pas encore sortis)
    // DONT la réservation associée est terminée avant l'heure de vérification.
    // (Note: on ne compte PAS ceux qui ont un abonnement pour l'instant, 
    // on se concentre sur les réservations comme demandé)
    $sql = "
            SELECT COUNT(*) 
            FROM parking_sessions s
            INNER JOIN reservations r ON s.reservation_id = r.id
            WHERE s.parking_id = :parking_id
            AND s.exit_time IS NULL
            AND r.end_time < :check_time
        ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
      'parking_id' => $parkingId,
      'check_time' => $checkTime->format('Y-m-d H:i:s')
    ]);

    return (int) $stmt->fetchColumn();
  }

  private function hydrate(array $row): ParkingSession
  {
    return new ParkingSession(
      $row['id'],
      $row['parking_id'],
      $row['user_id'],
      $row['reservation_id'],
      new DateTimeImmutable($row['entry_time']),
      $row['exit_time'] ? new DateTimeImmutable($row['exit_time']) : null,
      (int) $row['price_paid']
    );
  }
}
