<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\UserSubscription;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\Domain\ValueObject\WeeklySchedule;
use DateTimeImmutable;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;

class MongoUserSubscriptionRepository implements UserSubscriptionRepositoryInterface
{
  private Collection $collection;

  public function __construct(Client $client, string $databaseName)
  {
    $this->collection = $client->selectDatabase($databaseName)->selectCollection('user_subscriptions');

    // Indexation pour les performances
    $this->collection->createIndex(['user_id' => 1]);
    $this->collection->createIndex(['parking_id' => 1]);
    // Index composé pour les recherches de dates actives
    $this->collection->createIndex(['parking_id' => 1, 'is_active' => 1, 'start_date' => 1]);
  }

  public function save(UserSubscription $subscription): void
  {
    // Conversion Dates -> BSON
    $startBson = new UTCDateTime($subscription->getStartDate()->getTimestamp() * 1000);
    $endBson = new UTCDateTime($subscription->getEndDate()->getTimestamp() * 1000);

    $data = [
      '_id' => $subscription->getId(),
      'user_id' => $subscription->getUserId(),
      'parking_id' => $subscription->getParkingId(),
      'plan_id' => $subscription->getPlanId(),
      'plan_name' => $subscription->getPlanName(),
      'price' => $subscription->getPrice(),
      'start_date' => $startBson,
      'end_date' => $endBson,

      // Mongo stocke le tableau nativement, pas besoin de string JSON
      'schedule' => $subscription->getSchedule()->toArray(),

      'is_active' => $subscription->isActive(),
      'created_at' => new UTCDateTime() // Timestamp création
    ];

    $this->collection->updateOne(
      ['_id' => $subscription->getId()],
      ['$set' => $data],
      ['upsert' => true]
    );
  }

  public function findActiveOverlappingRange(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): array
  {
    $bsonStart = new UTCDateTime($start->getTimestamp() * 1000);
    $bsonEnd = new UTCDateTime($end->getTimestamp() * 1000);

    // start_date <= req_end AND end_date >= req_start
    $cursor = $this->collection->find([
      'parking_id' => $parkingId,
      'is_active' => true,
      'start_date' => ['$lte' => $bsonEnd],
      'end_date' => ['$gte' => $bsonStart]
    ]);

    $results = [];
    foreach ($cursor as $doc) {
      $results[] = $this->hydrate((array)$doc);
    }
    return $results;
  }

  public function findActiveForUser(string $userId, string $parkingId, DateTimeImmutable $now): ?UserSubscription
  {
    $bsonNow = new UTCDateTime($now->getTimestamp() * 1000);

    // On récupère tous les candidats potentiels (actifs et dans les dates)
    $cursor = $this->collection->find([
      'user_id' => $userId,
      'parking_id' => $parkingId,
      'is_active' => true,
      'start_date' => ['$lte' => $bsonNow],
      'end_date' => ['$gt' => $bsonNow]
    ]);

    // Vérification logicielle (Domain Logic) des horaires
    // Exactement comme dans la version SQL
    foreach ($cursor as $doc) {
      $sub = $this->hydrate((array)$doc);
      if ($sub->occupiesSpotAt($now)) {
        return $sub;
      }
    }

    return null;
  }

  public function countActiveForParking(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): int
  {
    $bsonStart = new UTCDateTime($start->getTimestamp() * 1000);
    $bsonEnd = new UTCDateTime($end->getTimestamp() * 1000);

    return $this->collection->countDocuments([
      'parking_id' => $parkingId,
      'is_active' => true,
      'start_date' => ['$lt' => $bsonEnd],
      'end_date' => ['$gt' => $bsonStart]
    ]);
  }

  public function findByUserId(string $userId): array
  {
    $cursor = $this->collection->find(
      ['user_id' => $userId],
      ['sort' => ['start_date' => -1]]
    );

    $results = [];
    foreach ($cursor as $doc) {
      $results[] = $this->hydrate((array)$doc);
    }
    return $results;
  }

  public function countActiveAt(string $parkingId, DateTimeImmutable $time): int
  {
    $bsonTime = new UTCDateTime($time->getTimestamp() * 1000);

    return $this->collection->countDocuments([
      'parking_id' => $parkingId,
      'is_active' => true,
      'start_date' => ['$lte' => $bsonTime],
      'end_date' => ['$gt' => $bsonTime]
    ]);
  }

  public function calculateRevenue(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): int
  {
    $bsonStart = new UTCDateTime($start->getTimestamp() * 1000);
    $bsonEnd = new UTCDateTime($end->getTimestamp() * 1000);

    $pipeline = [
      [
        '$match' => [
          'parking_id' => $parkingId,
          // On filtre sur la date d'achat (start_date) comme en SQL
          'start_date' => [
            '$gte' => $bsonStart,
            '$lte' => $bsonEnd
          ]
        ]
      ],
      [
        '$group' => [
          '_id' => null,
          'totalRevenue' => ['$sum' => '$price']
        ]
      ]
    ];

    $cursor = $this->collection->aggregate($pipeline);

    foreach ($cursor as $result) {
      return (int) $result['totalRevenue'];
    }

    return 0;
  }

  private function hydrate(array $doc): UserSubscription
  {
    // 1. Gestion du Timezone
    // On s'aligne sur la config globale (date_default_timezone_set du bootstrap)
    $appTimeZone = new \DateTimeZone(date_default_timezone_get());

    // 2. Gestion du Schedule (inchangé)
    $scheduleData = json_decode(json_encode($doc['schedule']), true) ?: [];
    $schedule = new WeeklySchedule($scheduleData);

    // 3. Dates
    /** @var UTCDateTime $startBson */
    $startBson = $doc['start_date'];

    /** @var UTCDateTime $endBson */
    $endBson = $doc['end_date'];

    return new UserSubscription(
      $doc['_id'],
      $doc['user_id'],
      $doc['parking_id'],
      $doc['plan_id'],
      $doc['plan_name'],
      (int)$doc['price'],

      // Conversion UTC -> Timezone App (Paris)
      $startBson->toDateTimeImmutable()->setTimezone($appTimeZone),
      $endBson->toDateTimeImmutable()->setTimezone($appTimeZone),

      $schedule,
      (bool)$doc['is_active']
    );
  }
}
