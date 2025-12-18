<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\UserSubscription;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\Domain\ValueObject\WeeklySchedule;
use DateTimeImmutable;
use PDO;

class SqlUserSubscriptionRepository implements UserSubscriptionRepositoryInterface
{
  public function __construct(private PDO $connection) {}

  public function save(UserSubscription $subscription): void
  {
    $sql = "INSERT INTO user_subscriptions 
                (id, user_id, parking_id, plan_id, start_date, end_date, schedule_json, is_active)
                VALUES (:id, :uid, :pid, :plan, :start, :end, :schedule, :active)";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      'id' => $subscription->getId(),
      'uid' => $subscription->getUserId(),
      'pid' => $subscription->getParkingId(),
      'plan' => $subscription->getPlanId(),
      'start' => $subscription->getStartDate()->format('Y-m-d H:i:s'),
      'end' => $subscription->getEndDate()->format('Y-m-d H:i:s'),
      'schedule' => json_encode($subscription->getSchedule()->toArray()),
      'active' => $subscription->isActive() ? 1 : 0
    ]);
  }

  public function findActiveOverlappingRange(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): array
  {
    // On cherche les abonnements qui sont actifs DANS les dates demandées.
    // (DateDebut_Abo <= DateFin_Demande) ET (DateFin_Abo >= DateDebut_Demande)
    $sql = "SELECT * FROM user_subscriptions 
                WHERE parking_id = :pid 
                AND is_active = 1
                AND start_date <= :req_end
                AND end_date >= :req_start";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      'pid' => $parkingId,
      'req_start' => $start->format('Y-m-d H:i:s'),
      'req_end' => $end->format('Y-m-d H:i:s')
    ]);

    $results = [];
    while ($row = $stmt->fetch()) {
      $results[] = $this->mapRowToEntity($row);
    }
    return $results;
  }

  private function mapRowToEntity(array $row): UserSubscription
  {
    // 1. Décodage du JSON pour reconstruire le VO WeeklySchedule
    $scheduleConfig = json_decode($row['schedule_json'] ?? '[]', true) ?: [];
    $schedule = new WeeklySchedule($scheduleConfig);

    return new UserSubscription(
      $row['id'],
      $row['user_id'],
      $row['parking_id'],
      $row['plan_id'],
      new DateTimeImmutable($row['start_date']),
      new DateTimeImmutable($row['end_date']),
      $schedule,
      (bool)$row['is_active']
    );
  }
}
