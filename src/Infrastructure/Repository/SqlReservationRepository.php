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

  public function countOverlappingReservations(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): int
  {
    // Logique SQL : Une période A chevauche une période B si :
    // (Debut_A < Fin_B) ET (Fin_A > Debut_B)
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
}
