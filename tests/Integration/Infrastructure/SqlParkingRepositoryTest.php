<?php

namespace Tests\Integration\Infrastructure;

use Tests\Integration\IntegrationTestCase;
use App\Infrastructure\Repository\SqlParkingRepository;
use App\Domain\Entity\Parking;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;

class SqlParkingRepositoryTest extends IntegrationTestCase
{
  private SqlParkingRepository $repo;

  protected function setUp(): void
  {
    // Appel du setUp parent pour initialiser la BDD
    parent::setUp();

    // On initialise juste le repo spécifique à ce test
    $this->repo = new SqlParkingRepository($this->pdo);
  }

  public function testItSavesAndRetrievesAParkingWithJsonData(): void
  {
    // ARRANGEMENT
    $id = 'uuid-test-123';
    $ownerId = 'owner-555';

    // On crée un parking avec des données complexes (JSON)
    $parking = new Parking(
      $id,
      $ownerId,
      'Parking Test Integration',
      new GpsCoordinates(48.8566, 2.3522), // Paris
      100,
      new PriceGrid([30 => 100, 60 => 200]), // 1€ les 30min, 2€ l'heure
      new WeeklySchedule([
        ['startDay' => 1, 'startTime' => '08:00', 'endDay' => 1, 'endTime' => '20:00']
      ])
    );

    // ACTION
    $this->repo->save($parking);

    // ASSERTION
    $saved = $this->repo->findById($id);

    $this->assertNotNull($saved, "Le parking devrait être trouvé en base");
    $this->assertEquals($id, $saved->getId());
    $this->assertEquals('Parking Test Integration', $saved->getName());

    // Vérification de la survie du JSON (PriceGrid)
    $this->assertEquals(200, $saved->calculatePrice(60));

    // Vérification de la survie du JSON (WeeklySchedule)
    $lundiMatin = new \DateTimeImmutable('Monday 10:00');
    $this->assertTrue($saved->getOpeningHours()->isOpen($lundiMatin));
  }

  public function testItUpdatesExistingParking(): void
  {
    $id = 'uuid-update-test';

    // 1. Création initiale
    $parking = new Parking(
      $id,
      'owner-1',
      'Nom Original',
      new GpsCoordinates(0, 0),
      10,
      new PriceGrid([60 => 1]),
      new WeeklySchedule([])
    );
    $this->repo->save($parking);

    // 2. Modification (Simulation du Use Case Update)
    // On recrée un objet avec le MÊME ID mais des données différentes
    $updatedParking = new Parking(
      $id,
      'owner-1',
      'Nom Modifié', // Changement
      new GpsCoordinates(10, 10), // Changement
      50, // Changement
      new PriceGrid([60 => 500]), // Changement prix
      new WeeklySchedule([])
    );

    $this->repo->save($updatedParking);

    // 3. Vérification
    $retrieved = $this->repo->findById($id);
    $this->assertEquals('Nom Modifié', $retrieved->getName());
    $this->assertEquals(50, $retrieved->getTotalPlaces());
    $this->assertEquals(500, $retrieved->calculatePrice(60));
  }

  public function testFindParkingsByOwner(): void
  {
    // Création de 2 parkings pour "owner-A"
    $p1 = new Parking('id-1', 'owner-A', 'P1', new GpsCoordinates(0, 0), 10, new PriceGrid([60 => 1]), new WeeklySchedule([]));
    $p2 = new Parking('id-2', 'owner-A', 'P2', new GpsCoordinates(0, 0), 10, new PriceGrid([60 => 1]), new WeeklySchedule([]));

    // Création de 1 parking pour "owner-B"
    $p3 = new Parking('id-3', 'owner-B', 'P3', new GpsCoordinates(0, 0), 10, new PriceGrid([60 => 1]), new WeeklySchedule([]));

    $this->repo->save($p1);
    $this->repo->save($p2);
    $this->repo->save($p3);

    // Action
    $results = $this->repo->findByOwnerId('owner-A');

    // Assertion
    $this->assertCount(2, $results);
    $this->assertEquals('owner-A', $results[0]->getOwnerId());
  }

  public function testItFindsParkingsByOwner(): void
  {
    // 1. On crée des données de test
    // Deux parkings pour l'owner-A
    $p1 = new Parking('id-1', 'owner-A', 'P1', new GpsCoordinates(0, 0), 10, new PriceGrid([60 => 1]), new WeeklySchedule([]));
    $p2 = new Parking('id-2', 'owner-A', 'P2', new GpsCoordinates(0, 0), 10, new PriceGrid([60 => 1]), new WeeklySchedule([]));

    // Un parking pour l'owner-B (pour vérifier qu'on ne le récupère pas)
    $p3 = new Parking('id-3', 'owner-B', 'P3', new GpsCoordinates(0, 0), 10, new PriceGrid([60 => 1]), new WeeklySchedule([]));

    $this->repo->save($p1);
    $this->repo->save($p2);
    $this->repo->save($p3);

    // 2. Action : on cherche ceux de owner-A
    $results = $this->repo->findByOwnerId('owner-A');

    // 3. Vérifications
    $this->assertCount(2, $results);

    // On vérifie qu'on a bien récupéré les bons IDs
    $ids = array_map(fn(Parking $p) => $p->getId(), $results);
    $this->assertContains('id-1', $ids);
    $this->assertContains('id-2', $ids);
    $this->assertNotContains('id-3', $ids);
  }

  public function testItDeletesAParking(): void
  {
    // 1. Création
    $id = 'uuid-delete-test';
    $parking = new Parking($id, 'owner-1', 'To Delete', new GpsCoordinates(0, 0), 10, new PriceGrid([60 => 1]), new WeeklySchedule([]));
    $this->repo->save($parking);

    // Vérif qu'il existe
    $this->assertNotNull($this->repo->findById($id));

    // 2. Suppression
    $this->repo->delete($id);

    // 3. Vérif qu'il n'existe plus
    $this->assertNull($this->repo->findById($id));
  }

  public function testFindAll(): void
  {
    // On vide tout (grâce au setUp c'est déjà vide, mais assurons-nous)
    // Note: Ici on ajoute 2 parkings
    $p1 = new Parking('id-all-1', 'o1', 'P1', new GpsCoordinates(0, 0), 10, new PriceGrid([60 => 1]), new WeeklySchedule([]));
    $this->repo->save($p1);

    $results = $this->repo->findAll();

    $this->assertNotEmpty($results);
    $this->assertInstanceOf(Parking::class, $results[0]);
  }
}
