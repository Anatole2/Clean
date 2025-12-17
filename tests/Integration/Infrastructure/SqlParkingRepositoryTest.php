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
  public function testItSavesAndRetrievesSubscriptionPlans(): void
  {
    // 1. Création d'un plan "Nuit"
    $nightRule = new WeeklySchedule([['startDay' => 1, 'startTime' => '18:00', 'endDay' => 2, 'endTime' => '08:00']]);
    $plan = new \App\Domain\ValueObject\SubscriptionPlan("Forfait Nuit", 5000, $nightRule);

    // 2. Création du parking avec ce plan
    $parking = new Parking(
      'uuid-plan-test',
      'owner-1',
      'Parking Plans',
      new GpsCoordinates(0, 0),
      10,
      new PriceGrid([60 => 1]),
      new WeeklySchedule([]),
      [$plan] // 👈 On injecte le plan
    );

    // 3. Sauvegarde
    $this->repo->save($parking);

    // 4. Récupération
    $saved = $this->repo->findById('uuid-plan-test');

    // 5. Vérifications
    $this->assertCount(1, $saved->getSubscriptionPlans());
    $this->assertEquals("Forfait Nuit", $saved->getSubscriptionPlans()[0]->getName());
    $this->assertEquals(5000, $saved->getSubscriptionPlans()[0]->getMonthlyPrice());
  }
  public function testFindByOwnerIdReturnsOnlyMatchingParkings(): void
  {
    // On définit une grille de prix valide pour passer la validation du Value Object
    // (ex: 60 minutes = 200 centimes)
    $dummyPriceGrid = new PriceGrid([60 => 200]);

    // On définit aussi un horaire vide ou simple (selon ton VO WeeklySchedule)
    $dummySchedule = new WeeklySchedule([]);

    // 1. On insère 3 parkings en base

    // Parking A (Owner 1)
    $parking1 = new Parking(
      'p1',
      'owner-1',
      'Parking A',
      new GpsCoordinates(48.0, 2.0),
      100,
      $dummyPriceGrid, // ✅ On passe une grille valide
      $dummySchedule,
      []
    );
    $this->repo->save($parking1);

    // Parking B (Owner 1)
    $parking2 = new Parking(
      'p2',
      'owner-1',
      'Parking B',
      new GpsCoordinates(48.1, 2.1),
      200,
      $dummyPriceGrid, // ✅ On passe une grille valide
      $dummySchedule,
      []
    );
    $this->repo->save($parking2);

    // Parking C (Owner 2) -> Celui-ci ne doit PAS ressortir
    $parking3 = new Parking(
      'p3',
      'owner-2',
      'Parking Intruder',
      new GpsCoordinates(49.0, 3.0),
      50,
      $dummyPriceGrid, // ✅ On passe une grille valide
      $dummySchedule,
      []
    );
    $this->repo->save($parking3);

    // 2. On appelle la méthode à tester pour Owner 1
    $results = $this->repo->findByOwnerId('owner-1');

    // 3. Vérifications
    $this->assertCount(2, $results);

    // On vérifie que ce sont bien les bons parkings via leurs IDs
    $ids = array_map(fn($p) => $p->getId(), $results);
    $this->assertContains('p1', $ids);
    $this->assertContains('p2', $ids);
    $this->assertNotContains('p3', $ids); // Le parking de l'autre owner n'est pas là

    // Petite vérification supplémentaire sur le nom
    // (L'ordre n'est pas garanti en SQL sans ORDER BY, donc on vérifie juste que le nom existe)
    $names = array_map(fn($p) => $p->getName(), $results);
    $this->assertContains('Parking A', $names);
    $this->assertContains('Parking B', $names);
  }

  public function testFindByOwnerIdReturnsEmptyIfNoneFound(): void
  {
    $results = $this->repo->findByOwnerId('unknown-owner');
    $this->assertEmpty($results);
  }
  public function testFindNearbyReturnsParkingsInRadiusOrderedByDistance(): void
  {
    // ARRANGE : On prépare 3 parkings
    $dummyPrice = new PriceGrid([60 => 100]);
    $dummySchedule = new WeeklySchedule([]);

    // 1. Parking au centre de Paris (Notre-Dame) - CIBLE (0 km)
    // Coordonnées : 48.8530, 2.3499
    $centerParis = new Parking(
      'p-center',
      'o1',
      'Parking Centre',
      new GpsCoordinates(48.8530, 2.3499),
      10,
      $dummyPrice,
      $dummySchedule
    );

    // 2. Parking à la Défense (environ 8-9 km du centre) - DANS LE RAYON DE 10KM
    // Coordonnées : 48.8924, 2.2361
    $defense = new Parking(
      'p-defense',
      'o1',
      'Parking Defense',
      new GpsCoordinates(48.8924, 2.2361),
      10,
      $dummyPrice,
      $dummySchedule
    );

    // 3. Parking à Versailles (environ 17-20 km du centre) - HORS RAYON DE 10KM
    // Coordonnées : 48.8049, 2.1204
    $versailles = new Parking(
      'p-versailles',
      'o1',
      'Parking Versailles',
      new GpsCoordinates(48.8049, 2.1204),
      10,
      $dummyPrice,
      $dummySchedule
    );

    $this->repo->save($centerParis);
    $this->repo->save($defense);
    $this->repo->save($versailles);

    // ACT : Recherche à partir du centre de Paris, rayon 10 km
    $searchCenter = new GpsCoordinates(48.8530, 2.3499);
    $results = $this->repo->findNearby($searchCenter, 10.0);

    // ASSERT
    // On s'attend à trouver le Centre (0km) et la Défense (~9km), mais PAS Versailles (~17km)
    $this->assertCount(2, $results, "Devrait trouver 2 parkings sur 3");

    // Vérification de l'ordre : Le premier doit être le plus proche (Centre)
    $this->assertEquals('p-center', $results[0]->getId());
    $this->assertEquals('p-defense', $results[1]->getId());

    // Vérification que Versailles est bien exclu
    $ids = array_map(fn($p) => $p->getId(), $results);
    $this->assertNotContains('p-versailles', $ids);
  }
}
