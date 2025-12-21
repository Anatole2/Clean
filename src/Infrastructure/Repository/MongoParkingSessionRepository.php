<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\ParkingSession;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use DateTimeImmutable;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;

class MongoParkingSessionRepository implements ParkingSessionRepositoryInterface
{
  private Collection $collection;

  public function __construct(Client $client, string $databaseName)
  {
    $this->collection = $client->selectDatabase($databaseName)->selectCollection('parking_sessions');

    // Indexes pour optimiser les recherches fréquentes
    $this->collection->createIndex(['user_id' => 1]);
    $this->collection->createIndex(['parking_id' => 1]);
    // Index composé pour trouver rapidement les sessions actives d'un parking
    $this->collection->createIndex(['parking_id' => 1, 'exit_time' => 1]);
  }

  public function save(ParkingSession $session): void
  {
    // Gestion du champ nullable exit_time
    $exitTimeBson = null;
    if ($session->getExitTime()) {
      $exitTimeBson = new UTCDateTime($session->getExitTime()->getTimestamp() * 1000);
    }

    $data = [
      '_id' => $session->getId(),
      'parking_id' => $session->getParkingId(),
      'user_id' => $session->getUserId(),
      'reservation_id' => $session->getReservationId(),

      // Dates en format BSON
      'entry_time' => new UTCDateTime($session->getEntryTime()->getTimestamp() * 1000),
      'exit_time' => $exitTimeBson,

      'price_paid' => $session->getPricePaid()
    ];

    $this->collection->updateOne(
      ['_id' => $session->getId()],
      ['$set' => $data],
      ['upsert' => true]
    );
  }

  public function findById(string $id): ?ParkingSession
  {
    $doc = $this->collection->findOne(['_id' => $id]);
    return $doc ? $this->hydrate((array)$doc) : null;
  }

  public function findActiveByUser(string $userId): ?ParkingSession
  {
    // WHERE user_id = :uid AND exit_time IS NULL
    $doc = $this->collection->findOne([
      'user_id' => $userId,
      'exit_time' => null
    ]);

    return $doc ? $this->hydrate((array)$doc) : null;
  }

  public function findByUserId(string $userId): array
  {
    $cursor = $this->collection->find(
      ['user_id' => $userId],
      ['sort' => ['entry_time' => -1]] // ORDER BY entry_time DESC
    );

    $sessions = [];
    foreach ($cursor as $doc) {
      $sessions[] = $this->hydrate((array)$doc);
    }
    return $sessions;
  }

  public function findByParkingId(string $parkingId): array
  {
    $cursor = $this->collection->find(
      ['parking_id' => $parkingId],
      ['sort' => ['entry_time' => -1]]
    );

    $sessions = [];
    foreach ($cursor as $doc) {
      $sessions[] = $this->hydrate((array)$doc);
    }
    return $sessions;
  }

  public function findActiveSessionsByParkingId(string $parkingId): array
  {
    $cursor = $this->collection->find([
      'parking_id' => $parkingId,
      'exit_time' => null
    ]);

    $sessions = [];
    foreach ($cursor as $doc) {
      $sessions[] = $this->hydrate((array)$doc);
    }
    return $sessions;
  }

  /**
   * 🌶️ LE CHALLENGE : Jointure SQL en Mongo
   * On utilise un Pipeline d'Agrégation ($lookup)
   */
  public function countOverstayingCars(string $parkingId, DateTimeImmutable $checkTime): int
  {
    $bsonCheckTime = new UTCDateTime($checkTime->getTimestamp() * 1000);

    $pipeline = [
      // 1. WHERE : On filtre d'abord les sessions actives de ce parking
      [
        '$match' => [
          'parking_id' => $parkingId,
          'exit_time' => null
        ]
      ],
      // 2. JOIN : On va chercher la réservation correspondante
      [
        '$lookup' => [
          'from' => 'reservations',       // La collection à joindre
          'localField' => 'reservation_id', // Champ dans parking_sessions
          'foreignField' => '_id',        // Champ dans reservations
          'as' => 'reservation_data'      // Nom du tableau temporaire de résultat
        ]
      ],
      // 3. UNWIND : $lookup renvoie un tableau, on le met à plat pour filtrer
      [
        '$unwind' => '$reservation_data'
      ],
      // 4. WHERE (Sur la table jointe) : r.end_time < :check_time
      [
        '$match' => [
          'reservation_data.end_time' => ['$lt' => $bsonCheckTime]
        ]
      ],
      // 5. COUNT : On compte les résultats restants
      [
        '$count' => 'total_overstaying'
      ]
    ];

    $cursor = $this->collection->aggregate($pipeline);

    // Mongo renvoie un curseur, on récupère le premier résultat
    foreach ($cursor as $result) {
      return (int) $result['total_overstaying'];
    }

    return 0;
  }

  private function hydrate(array $doc): ParkingSession
  {
    // 1. On récupère le Timezone global de l'app (défini dans le bootstrap)
    $appTimeZone = new \DateTimeZone(date_default_timezone_get());

    /** @var UTCDateTime $entry */
    $entry = $doc['entry_time'];
    // Conversion UTC -> Timezone App (Paris)
    $entryDate = $entry->toDateTimeImmutable()->setTimezone($appTimeZone);

    /** @var UTCDateTime|null $exit */
    $exit = $doc['exit_time'] ?? null;
    $exitDate = null;

    if ($exit) {
      // Conversion UTC -> Timezone App (Paris) seulement si existe
      $exitDate = $exit->toDateTimeImmutable()->setTimezone($appTimeZone);
    }

    return new ParkingSession(
      $doc['_id'],
      $doc['parking_id'],
      $doc['user_id'],
      $doc['reservation_id'],
      $entryDate,
      $exitDate,
      (int) $doc['price_paid']
    );
  }
}
