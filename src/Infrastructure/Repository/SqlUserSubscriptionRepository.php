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
    $stmt = $this->connection->prepare("
            INSERT INTO user_subscriptions 
            (id, user_id, parking_id, plan_id, plan_name, price, start_date, end_date, schedule_json, is_active, created_at)
            VALUES 
            (:id, :user_id, :parking_id, :plan_id, :plan_name, :price, :start_date, :end_date, :schedule, :is_active, NOW())
        ");

    $stmt->execute([
      'id' => $subscription->getId(),
      'user_id' => $subscription->getUserId(),
      'parking_id' => $subscription->getParkingId(),
      'plan_id' => $subscription->getPlanId(),
      'plan_name' => $subscription->getPlanName(),
      'price' => $subscription->getPrice(),
      'start_date' => $subscription->getStartDate()->format('Y-m-d H:i:s'),
      'end_date' => $subscription->getEndDate()->format('Y-m-d H:i:s'),
      'schedule' => json_encode($subscription->getSchedule()->toArray()),
      'is_active' => $subscription->isActive() ? 1 : 0
    ]);
  }

  public function findActiveOverlappingRange(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): array
  {
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
    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      // CORRECTION : On utilise hydrate ici aussi
      $results[] = $this->hydrate($row);
    }
    return $results;
  }

  public function findActiveForUser(string $userId, string $parkingId, \DateTimeImmutable $now): ?\App\Domain\Entity\UserSubscription
  {
    $stmt = $this->connection->prepare("
            SELECT * FROM user_subscriptions 
            WHERE user_id = :userId 
            AND parking_id = :parkingId
            AND is_active = 1
            AND start_date <= :now 
            AND end_date > :now
            LIMIT 1 
        ");

    $stmt->execute([
      'userId' => $userId,
      'parkingId' => $parkingId,
      'now' => $now->format('Y-m-d H:i:s')
    ]);

    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
      // CORRECTION : hydrate est maintenant bien défini
      $sub = $this->hydrate($row);

      // Vérification logicielle des horaires (Schedule)
      if ($sub->occupiesSpotAt($now)) {
        return $sub;
      }
    }

    return null;
  }
  public function countActiveForParking(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): int
  {
    // On compte les abonnements dont la période chevauche [start, end]
    // (Debut_Sub < Fin_Req) ET (Fin_Sub > Debut_Req)
    $sql = "SELECT COUNT(*) FROM user_subscriptions 
                WHERE parking_id = :pid 
                AND is_active = 1
                AND start_date < :end_req 
                AND end_date > :start_req";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      'pid' => $parkingId,
      'start_req' => $start->format('Y-m-d H:i:s'),
      'end_req' => $end->format('Y-m-d H:i:s')
    ]);

    return (int) $stmt->fetchColumn();
  }

  public function findByUserId(string $userId): array
  {
    $stmt = $this->connection->prepare("SELECT * FROM user_subscriptions WHERE user_id = :uid ORDER BY start_date DESC");
    $stmt->execute(['uid' => $userId]);

    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $results[] = $this->hydrate($row);
    }
    return $results;
  }
  private function hydrate(array $row): UserSubscription
  {
    $scheduleConfig = json_decode($row['schedule_json'] ?? '[]', true) ?: [];
    $schedule = new WeeklySchedule($scheduleConfig);

    return new UserSubscription(
      $row['id'],
      $row['user_id'],
      $row['parking_id'],
      $row['plan_id'],
      $row['plan_name'],
      $row['price'],
      new DateTimeImmutable($row['start_date']),
      new DateTimeImmutable($row['end_date']),
      $schedule,
      (bool)$row['is_active']
    );
  }
}
