<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository; // Attention à la majuscule R

use App\Domain\Entity\Parking;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;
use App\Domain\ValueObject\SubscriptionPlan;
use MongoDB\Client;
use MongoDB\Collection;

class MongoParkingRepository implements ParkingRepositoryInterface
{
  private Collection $collection;

  public function __construct(Client $client, string $databaseName)
  {
    $this->collection = $client->selectDatabase($databaseName)->selectCollection('parkings');

    $this->collection->createIndex(['location' => '2dsphere']);
  }

  public function save(Parking $parking): void
  {
    // 1. Transformation des données pour Mongo
    $data = [
      '_id' => $parking->getId(),
      'owner_id' => $parking->getOwnerId(),
      'name' => $parking->getName(),

      'location' => [
        'type' => 'Point',
        'coordinates' => [
          $parking->getCoordinates()->getLongitude(),
          $parking->getCoordinates()->getLatitude()
        ]
      ],

      'total_places' => $parking->getTotalPlaces(),

      // Stockage direct des tableaux (Pas de json_encode string)
      'price_grid' => $parking->getPriceGrid()->toArray(),
      'opening_hours' => $parking->getOpeningHours()->toArray(),

      // Transformation des objets SubscriptionPlan en tableaux simples
      'subscription_plans' => array_map(
        fn($plan) => $plan->toArray(),
        $parking->getSubscriptionPlans()
      )
    ];

    // 2. Upsert (Insert ou Update)
    $this->collection->updateOne(
      ['_id' => $parking->getId()],
      ['$set' => $data],
      ['upsert' => true]
    );
  }

  public function findById(string $id): ?Parking
  {
    $document = $this->collection->findOne(['_id' => $id]);

    if (!$document) {
      return null;
    }

    return $this->mapDocumentToEntity((array) $document);
  }

  public function findAll(): array
  {
    $cursor = $this->collection->find([]);

    $parkings = [];
    foreach ($cursor as $document) {
      $parkings[] = $this->mapDocumentToEntity((array) $document);
    }

    return $parkings;
  }

  public function findByOwnerId(string $ownerId): array
  {
    $cursor = $this->collection->find(['owner_id' => $ownerId]);

    $parkings = [];
    foreach ($cursor as $document) {
      $parkings[] = $this->mapDocumentToEntity((array) $document);
    }

    return $parkings;
  }

  public function delete(string $id): void
  {
    $this->collection->deleteOne(['_id' => $id]);
  }

  /**
   * Recherche géographique native MongoDB ($near)
   * Remplace la formule Haversine SQL
   */
  public function findNearby(GpsCoordinates $center, float $radiusInKm): array
  {
    $cursor = $this->collection->find([
      'location' => [
        '$near' => [
          '$geometry' => [
            'type' => 'Point',
            'coordinates' => [$center->getLongitude(), $center->getLatitude()]
          ],
          // MongoDB attend des mètres, donc on multiplie par 1000
          '$maxDistance' => $radiusInKm * 1000
        ]
      ]
    ]);

    $parkings = [];
    foreach ($cursor as $document) {
      $parkings[] = $this->mapDocumentToEntity((array) $document);
    }

    return $parkings;
  }

  /**
   * Hydratation : Document Mongo -> Entité PHP
   */
  private function mapDocumentToEntity(array $doc): Parking
  {
    // 1. Extraction Lat/Lon depuis le GeoJSON
    $lon = $doc['location']['coordinates'][0];
    $lat = $doc['location']['coordinates'][1];

    // 2. Normalisation des données imbriquées
    $priceData = json_decode(json_encode($doc['price_grid']), true);
    $hoursData = json_decode(json_encode($doc['opening_hours']), true);
    $plansData = json_decode(json_encode($doc['subscription_plans']), true) ?: [];

    // 3. Reconstruction des SubscriptionPlans
    $plans = array_map(function (array $data) {
      return new SubscriptionPlan(
        $data['id'],
        $data['name'],
        (int)$data['price'],
        new WeeklySchedule($data['schedule'])
      );
    }, $plansData);

    // 4. Retour final de l'entité
    return new Parking(
      $doc['_id'],
      $doc['owner_id'],
      $doc['name'],
      new GpsCoordinates((float)$lat, (float)$lon),
      (int)$doc['total_places'],
      new PriceGrid($priceData),
      new WeeklySchedule($hoursData),
      $plans
    );
  }
}
