<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Reservation;
use App\Domain\Repository\ReservationRepositoryInterface;
use DateTimeImmutable;
use PDO;

class SqlReservationRepository implements ReservationRepositoryInterface
{
  public function __construct(private PDO $connection) {}

  public function save(Reservation $reservation): void
  {
    $sql = "INSERT INTO reservations (id, user_id, parking_id, start_time, end_time, price_paid, status)
                VALUES (:id, :uid, :pid, :start, :end, :price, :status)";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      'id' => $reservation->getId(),
      'uid' => $reservation->getUserId(),
      'pid' => $reservation->getParkingId(),
      'start' => $reservation->getStartTime()->format('Y-m-d H:i:s'),
      'end' => $reservation->getEndTime()->format('Y-m-d H:i:s'),
      'price' => $reservation->getPricePaidInCents(),
      'status' => $reservation->getStatus()
    ]);
  }
  public function findById(string $id): ?\App\Domain\Entity\Reservation
  {
    $stmt = $this->connection->prepare("SELECT * FROM reservations WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    return $row ? $this->hydrate($row) : null;
  }
  public function countOverlappingReservations(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): int
  {
    $sql = "SELECT COUNT(*) FROM reservations 
                WHERE parking_id = :pid
                AND status = 'CONFIRMED'
                AND start_time < :req_end 
                AND end_time > :req_start";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      'pid' => $parkingId,
      'req_start' => $start->format('Y-m-d H:i:s'),
      'req_end'   => $end->format('Y-m-d H:i:s')
    ]);

    return (int)$stmt->fetchColumn();
  }

  public function findActiveForUser(string $userId, string $parkingId, \DateTimeImmutable $now): ?\App\Domain\Entity\Reservation
  {
    $stmt = $this->connection->prepare("
            SELECT * FROM reservations 
            WHERE user_id = :userId 
            AND parking_id = :parkingId
            AND status = 'CONFIRMED'
            AND start_time <= :now 
            AND end_time > :now
            LIMIT 1
        ");

    $stmt->execute([
      'userId' => $userId,
      'parkingId' => $parkingId,
      'now' => $now->format('Y-m-d H:i:s')
    ]);

    $row = $stmt->fetch(\PDO::FETCH_ASSOC);

    // CORRECTION : Appel à la méthode qu'on définit juste en dessous
    return $row ? $this->hydrate($row) : null;
  }

  private function hydrate(array $row): Reservation
  {
    return new Reservation(
      $row['id'],
      $row['user_id'],
      $row['parking_id'],
      new DateTimeImmutable($row['start_time']),
      new DateTimeImmutable($row['end_time']),
      (int) $row['price_paid'],
      $row['status'] ?? 'CONFIRMED'
    );
  }
}
