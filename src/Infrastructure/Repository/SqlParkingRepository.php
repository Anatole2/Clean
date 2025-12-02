<?php

declare(strict_types=1);

namespace App\Infrastructure\repository;

use App\Domain\Entity\Parking;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;
use App\Domain\ValueObject\SubscriptionPlan;
use PDO;
use Exception;

class SqlParkingRepository implements ParkingRepositoryInterface
{
  public function __construct(
    private PDO $pdo
  ) {}

  public function save(Parking $parking): void
  {
    // Regarde bien le nombre de paramètres ci-dessous
    $sql = "
            INSERT INTO parkings (
                id, owner_id, name, latitude, longitude, total_places, 
                price_grid, opening_hours, subscription_plans
            )
            VALUES (
                :id, :owner_id, :name, :lat, :lon, :total, 
                :prices, :hours, :plans  
            )
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                total_places = VALUES(total_places),
                price_grid = VALUES(price_grid),
                opening_hours = VALUES(opening_hours),
                subscription_plans = VALUES(subscription_plans)
        ";

    $stmt = $this->pdo->prepare($sql);

    $stmt->execute([
      'id'       => $parking->getId(),
      'owner_id' => $parking->getOwnerId(),
      'name'     => $parking->getName(),
      'lat'      => $parking->getCoordinates()->getLatitude(),
      'lon'      => $parking->getCoordinates()->getLongitude(),
      'total'    => $parking->getTotalPlaces(),
      'prices'   => json_encode($parking->getPriceGrid()->toArray()),
      'hours'    => json_encode($parking->getOpeningHours()->toArray()),

      // Le jeton :plans correspond à cette ligne
      'plans'    => json_encode(array_map(
        fn($plan) => $plan->toArray(),
        $parking->getSubscriptionPlans()
      ))
    ]);
  }

  public function findById(string $id): ?Parking
  {
    $stmt = $this->pdo->prepare("SELECT * FROM parkings WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      return null;
    }

    return $this->mapRowToEntity($row);
  }
  public function delete(string $id): void
  {
    $stmt = $this->pdo->prepare("DELETE FROM parkings WHERE id = :id");
    $stmt->execute(['id' => $id]);
  }

  public function findAll(): array
  {
    $stmt = $this->pdo->query("SELECT * FROM parkings");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $parkings = [];
    foreach ($rows as $row) {
      // On réutilise la logique de mapping pour chaque ligne
      $parkings[] = $this->mapRowToEntity($row);
    }

    return $parkings;
  }
  // Méthode helper pour éviter la duplication de code si tu ajoutes findAll()
  private function mapRowToEntity(array $row): Parking
  {
    // 1. Décodage du JSON
    $rawPlans = json_decode($row['subscription_plans'] ?? '[]', true) ?: [];

    // 2. Reconstruction des objets
    $plans = array_map(function (array $data) {
      return new SubscriptionPlan(
        $data['name'],
        (int)$data['price'],
        new WeeklySchedule($data['rule'])
      );
    }, $rawPlans);

    return new Parking(
      $row['id'],
      $row['owner_id'],
      $row['name'],
      new GpsCoordinates((float)$row['latitude'], (float)$row['longitude']),
      (int)$row['total_places'],
      new PriceGrid(json_decode($row['price_grid'], true)),
      new WeeklySchedule(json_decode($row['opening_hours'], true)),
      $plans
    );
  }
  public function findByOwnerId(string $ownerId): array
  {
    $stmt = $this->pdo->prepare("SELECT * FROM parkings WHERE owner_id = :owner_id");
    $stmt->execute(['owner_id' => $ownerId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $parkings = [];
    foreach ($rows as $row) {
      $parkings[] = $this->mapRowToEntity($row);
    }

    return $parkings;
  }
  public function findNearby(GpsCoordinates $center, float $radiusInKm): array
  {
    throw new Exception("TODO");
  }
  // La fonction findNearby() utilisant la formule de Haversine est commentée ci-dessous.
  /* public function findNearby(GpsCoordinates $center, float $radiusInKm): array */
  /*     { */
  /*         // Formule de Haversine pour calculer la distance en km directement en SQL */
  /*         // 6371 est le rayon de la Terre en km */
  /*         $sql = " */
  /*             SELECT *,  */
  /*             (6371 * acos( */
  /*                 cos(radians(:lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians(:lon))  */
  /*                 + sin(radians(:lat)) * sin(radians(latitude)) */
  /*             )) AS distance  */
  /*             FROM parkings  */
  /*             HAVING distance < :radius  */
  /*             ORDER BY distance ASC */
  /*         "; */
  /**/
  /*         $stmt = $this->pdo->prepare($sql); */
  /*         $stmt->execute([ */
  /*             'lat' => $center->getLatitude(), */
  /*             'lon' => $center->getLongitude(), */
  /*             'radius' => $radiusInKm */
  /*         ]); */
  /**/
  /*         $rows = $stmt->fetchAll(PDO::FETCH_ASSOC); */
  /**/
  /*         $parkings = []; */
  /*         foreach ($rows as $row) { */
  /*             $parkings[] = $this->mapRowToEntity($row); */
  /*         } */
  /**/
  /*         return $parkings; */
  /*     } */
  // TODO: Implémenter delete(), findAll(), etc. selon l'interface définie
}
