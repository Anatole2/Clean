<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Reservation;
use App\Domain\Repository\ReservationRepositoryInterface;
use DateTimeImmutable;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;

class MongoReservationRepository implements ReservationRepositoryInterface
{
  private Collection $collection;

  public function __construct(Client $client, string $databaseName)
  {
    $this->collection = $client->selectDatabase($databaseName)->selectCollection('reservations');

    // Index recommandés pour la performance (optionnel mais bien vu)
    // On cherche souvent par parking_id ou user_id
    $this->collection->createIndex(['parking_id' => 1]);
    $this->collection->createIndex(['user_id' => 1]);
  }

  public function save(Reservation $reservation): void
  {
    $data = [
      '_id' => $reservation->getId(),
      'user_id' => $reservation->getUserId(),
      'parking_id' => $reservation->getParkingId(),

      // 📅 MONGODB DATE : Conversion DateTimeImmutable -> BSON UTCDateTime
      // Multiplié par 1000 car Mongo stocke des millisecondes
      'start_time' => new UTCDateTime($reservation->getStartTime()->getTimestamp() * 1000),
      'end_time' => new UTCDateTime($reservation->getEndTime()->getTimestamp() * 1000),

      'price_paid' => $reservation->getPricePaidInCents(),
      'status' => $reservation->getStatus()
    ];

    $this->collection->updateOne(
      ['_id' => $reservation->getId()],
      ['$set' => $data],
      ['upsert' => true]
    );
  }

  public function findById(string $id): ?Reservation
  {
    $doc = $this->collection->findOne(['_id' => $id]);
    return $doc ? $this->hydrate((array)$doc) : null;
  }

  public function findByUserId(string $userId): array
  {
    // 'sort' => ['start_time' => -1] équivaut à ORDER BY start_time DESC (-1 = DESC, 1 = ASC)
    $cursor = $this->collection->find(
      ['user_id' => $userId],
      ['sort' => ['start_time' => -1]]
    );

    $reservations = [];
    foreach ($cursor as $doc) {
      $reservations[] = $this->hydrate((array)$doc);
    }
    return $reservations;
  }

  public function findByParkingId(string $parkingId): array
  {
    $cursor = $this->collection->find(
      ['parking_id' => $parkingId],
      ['sort' => ['start_time' => -1]]
    );

    $reservations = [];
    foreach ($cursor as $doc) {
      $reservations[] = $this->hydrate((array)$doc);
    }
    return $reservations;
  }

  // ⚡️ Logique de chevauchement (Overlapping)
  public function countOverlappingReservations(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): int
  {
    // Conversion des dates requises en format Mongo
    $bsonStart = new UTCDateTime($start->getTimestamp() * 1000);
    $bsonEnd = new UTCDateTime($end->getTimestamp() * 1000);

    // SQL: start_time < :req_end AND end_time > :req_start
    $count = $this->collection->countDocuments([
      'parking_id' => $parkingId,
      'status' => 'CONFIRMED',
      'start_time' => ['$lt' => $bsonEnd],   // < fin demandée
      'end_time' => ['$gt' => $bsonStart]    // > début demandé
    ]);

    return $count;
  }

  // ⚡️ Trouver une réservation active à l'instant T
  public function findActiveForUser(string $userId, string $parkingId, DateTimeImmutable $now): ?Reservation
  {
    $bsonNow = new UTCDateTime($now->getTimestamp() * 1000);

    $doc = $this->collection->findOne([
      'user_id' => $userId,
      'parking_id' => $parkingId,
      'status' => 'CONFIRMED',
      'start_time' => ['$lte' => $bsonNow], // <= Now
      'end_time' => ['$gt' => $bsonNow]     // > Now
    ]);

    return $doc ? $this->hydrate((array)$doc) : null;
  }

  public function countActiveAt(string $parkingId, DateTimeImmutable $time): int
  {
    $bsonTime = new UTCDateTime($time->getTimestamp() * 1000);

    return $this->collection->countDocuments([
      'parking_id' => $parkingId,
      'status' => 'CONFIRMED',
      'start_time' => ['$lte' => $bsonTime],
      'end_time' => ['$gt' => $bsonTime]
    ]);
  }

  // 💰 CALCUL DU REVENU (Agrégation)
  public function calculateRevenue(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): int
  {
    $bsonStart = new UTCDateTime($start->getTimestamp() * 1000);
    $bsonEnd = new UTCDateTime($end->getTimestamp() * 1000);

    // En Mongo, pour faire une somme, on utilise un Pipeline d'Agrégation
    $pipeline = [
      // 1. Filtrer les documents (WHERE)
      [
        '$match' => [
          'parking_id' => $parkingId,
          'status' => 'CONFIRMED',
          'end_time' => [
            '$gte' => $bsonStart, // >= Start
            '$lte' => $bsonEnd    // <= End
          ]
        ]
      ],
      // 2. Grouper et Sommer (GROUP BY / SUM)
      [
        '$group' => [
          '_id' => null, // On groupe tout en un seul résultat
          'totalRevenue' => ['$sum' => '$price_paid']
        ]
      ]
    ];

    $cursor = $this->collection->aggregate($pipeline);

    // Récupérer le premier résultat
    foreach ($cursor as $result) {
      return (int) $result['totalRevenue'];
    }

    return 0; // Si aucun résultat
  }

  /**
   * Hydratation : Document Mongo (avec BSON Date) -> Entité PHP
   */
  private function hydrate(array $doc): Reservation
  {
    // Conversion inverse : BSON UTCDateTime -> PHP DateTimeImmutable
    /** @var UTCDateTime $bsonStart */
    $bsonStart = $doc['start_time'];
    /** @var UTCDateTime $bsonEnd */
    $bsonEnd = $doc['end_time'];

    // 1. On récupère la date brute (UTC)
    $start = $bsonStart->toDateTimeImmutable();
    $end   = $bsonEnd->toDateTimeImmutable();

    // date_default_timezone_get() récupérera 'Europe/Paris' défini dans l'étape 1
    $appTimeZone = new \DateTimeZone(date_default_timezone_get());

    $start = $start->setTimezone($appTimeZone);
    $end   = $end->setTimezone($appTimeZone);

    return new Reservation(
      $doc['_id'],
      $doc['user_id'],
      $doc['parking_id'],
      $start, // Méthode native très pratique !
      $end,
      (int) $doc['price_paid'],
      $doc['status'] ?? 'CONFIRMED'
    );
  }
}
