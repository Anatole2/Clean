<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use App\Domain\Entity\ParkingSession;
use App\Infrastructure\Repository\SqlParkingSessionRepository;
use Tests\Integration\IntegrationTestCase;
use DateTimeImmutable;

class SqlParkingSessionRepositoryTest extends IntegrationTestCase
{
  private SqlParkingSessionRepository $repo;

  // Surcharge du setUp pour instancier le Repo et les données de test
  protected function setUp(): void
  {
    parent::setUp();

    $this->repo = new SqlParkingSessionRepository($this->pdo);

    // On prépare les données de base (User + Parking) pour éviter les erreurs de Foreign Key
    $this->createDummyData();
  }

  public function testItSavesAndRetrievesSession(): void
  {
    $session = new ParkingSession(
      'sess-1',
      'p1',
      'u1',
      'res-1',
      new DateTimeImmutable('2024-01-01 10:00:00')
    );

    $this->repo->save($session);

    $found = $this->repo->findById('sess-1');

    $this->assertNotNull($found);
    $this->assertEquals('sess-1', $found->getId());
    $this->assertTrue($found->isOngoing());
  }

  public function testItUpdatesSessionOnSave(): void
  {
    // 1. Création
    $session = new ParkingSession(
      'sess-1',
      'p1',
      'u1',
      'res-1',
      new DateTimeImmutable('2024-01-01 10:00:00')
    );
    $this->repo->save($session);

    // 2. Mise à jour (Sortie)
    $session->close(new DateTimeImmutable('2024-01-01 12:00:00'), 500);
    $this->repo->save($session);

    // 3. Vérification
    $found = $this->repo->findById('sess-1');
    $this->assertFalse($found->isOngoing());
    $this->assertEquals(500, $found->getPricePaid());
  }

  public function testFindActiveByUser(): void
  {
    // Session fermée (ne doit pas être trouvée)
    $closed = new ParkingSession('s-closed', 'p1', 'u1', 'res-1', new DateTimeImmutable());
    $closed->close(new DateTimeImmutable(), 100);
    $this->repo->save($closed);

    // Session active (doit être trouvée)
    $active = new ParkingSession('s-active', 'p1', 'u1', 'res-2', new DateTimeImmutable());
    $this->repo->save($active);

    $found = $this->repo->findActiveByUser('u1');

    $this->assertNotNull($found);
    $this->assertEquals('s-active', $found->getId());
  }

  public function testCountOverstayingCars(): void
  {
    // SCÉNARIO : Il est 14:00
    $checkTime = new DateTimeImmutable('2024-01-01 14:00:00');

    // Cas 1 : User normal (Résa 13h-15h). Il est là, c'est OK.
    $this->createReservation('res-ok', 'p1', 'u1', '2024-01-01 13:00', '2024-01-01 15:00');
    $s1 = new ParkingSession('s1', 'p1', 'u1', 'res-ok', new DateTimeImmutable('2024-01-01 13:05'));
    $this->repo->save($s1);

    // Cas 2 : Le Squatteur (Résa 10h-12h). Il est encore là à 14h ! -> DOIT COMPTER
    $this->createReservation('res-late', 'p1', 'u1', '2024-01-01 10:00', '2024-01-01 12:00');
    $s2 = new ParkingSession('s2', 'p1', 'u1', 'res-late', new DateTimeImmutable('2024-01-01 10:00'));
    $this->repo->save($s2);

    // Cas 3 : L'honnête citoyen (Résa 10h-12h). Il est parti à 12h. -> OK
    $this->createReservation('res-gone', 'p1', 'u1', '2024-01-01 10:00', '2024-01-01 12:00');
    $s3 = new ParkingSession('s3', 'p1', 'u1', 'res-gone', new DateTimeImmutable('2024-01-01 10:00'));
    $s3->close(new DateTimeImmutable('2024-01-01 12:00'), 200);
    $this->repo->save($s3);

    // ACT
    $count = $this->repo->countOverstayingCars('p1', $checkTime);

    // ASSERT : Seul s2 compte
    $this->assertEquals(1, $count);
  }
  public function testFindByUserIdReturnsCorrectSessionsOrderedByEntryTimeDesc(): void
  {
    // ARRANGE

    // Session 1 : Ancienne, Terminée
    $s1 = new ParkingSession(
      'sess-old',
      'p1',
      'u1',
      null,
      new DateTimeImmutable('2025-01-01 10:00'),
      new DateTimeImmutable('2025-01-01 12:00'),
      500
    );
    $this->repo->save($s1);

    // Session 2 : Récente, En cours (ExitTime NULL)
    $s2 = new ParkingSession(
      'sess-active',
      'p1',
      'u1',
      null,
      new DateTimeImmutable('2025-02-01 10:00'),
      null, // NULL
      0
    );
    $this->repo->save($s2);

    // Session 3 : Autre utilisateur (Ne doit pas être récupérée)
    $s3 = new ParkingSession(
      'sess-other',
      'p1',
      'u2',
      null,
      new DateTimeImmutable('2025-01-01 10:00'),
      null,
      0
    );
    $this->repo->save($s3);

    // ACT
    $results = $this->repo->findByUserId('u1');

    // ASSERT
    $this->assertCount(2, $results);

    // Vérification de l'ordre (DESC : Le plus récent en premier)
    $this->assertEquals('sess-active', $results[0]->getId());
    $this->assertEquals('sess-old', $results[1]->getId());

    // Vérification de l'hydratation (Gestion du NULL)
    $this->assertNull($results[0]->getExitTime());
    $this->assertNotNull($results[1]->getExitTime());
  }

  public function testFindByUserIdReturnsEmptyArrayIfNoSessions(): void
  {
    $results = $this->repo->findByUserId('user-sans-session');
    $this->assertIsArray($results);
    $this->assertEmpty($results);
  }
  public function testFindByParkingIdReturnsCorrectSessionsOrderedByEntryDesc(): void
  {
    // ARRANGE

    // Session 1 (P1) : Récente (12:00)
    $s1 = new ParkingSession(
      's-recent',
      'p1',
      'u1',
      null,
      new DateTimeImmutable('2024-01-01 12:00:00')
    );
    $this->repo->save($s1);

    // Session 2 (P1) : Ancienne (10:00)
    $s2 = new ParkingSession(
      's-old',
      'p1',
      'u1',
      null,
      new DateTimeImmutable('2024-01-01 10:00:00')
    );
    $this->repo->save($s2);

    // Session 3 (P2) : Autre parking (Ne doit pas remonter)
    $s3 = new ParkingSession(
      's-other',
      'p2',
      'u1',
      null,
      new DateTimeImmutable('2024-01-01 11:00:00')
    );
    $this->repo->save($s3);

    // ACT
    $results = $this->repo->findByParkingId('p1');

    // ASSERT
    $this->assertCount(2, $results);

    // Vérif Ordre (DESC)
    $this->assertEquals('s-recent', $results[0]->getId());
    $this->assertEquals('s-old', $results[1]->getId());

    // Vérif Parking ID
    $this->assertEquals('p1', $results[0]->getParkingId());
  }
  // --- Helpers ---

  private function createDummyData(): void
  {
    $this->pdo->exec("INSERT INTO accounts (id, email, password_hash, role) VALUES ('u1', 'test@test.com', 'hash', 'USER')");
    $this->pdo->exec("INSERT INTO accounts (id, email, password_hash, role) VALUES ('u2', 'other@test.com', 'hash', 'USER')");

    // Parking 1
    $this->pdo->exec("INSERT INTO parkings (id, name, latitude, longitude, total_places, price_grid, opening_hours, subscription_plans, owner_id) 
            VALUES ('p1', 'Parking Test', 0, 0, 10, '{}', '{}', '[]', 'u1')");

    // Parking 2 (AJOUTÉ POUR LE TEST DE FILTRAGE)
    $this->pdo->exec("INSERT INTO parkings (id, name, latitude, longitude, total_places, price_grid, opening_hours, subscription_plans, owner_id) 
            VALUES ('p2', 'Parking Other', 0, 0, 10, '{}', '{}', '[]', 'u1')");

    // Initialisation des FK pour les tests existants
    $this->createReservation('res-1', 'p1', 'u1', '2024-01-01 08:00', '2024-01-01 18:00');
    $this->createReservation('res-2', 'p1', 'u1', '2024-01-01 08:00', '2024-01-01 18:00');
  }

  private function createReservation(string $id, string $parkingId, string $userId, string $start, string $end): void
  {
    $stmt = $this->pdo->prepare("INSERT INTO reservations (id, user_id, parking_id, start_time, end_time, price_paid, status) 
            VALUES (?, ?, ?, ?, ?, 100, 'CONFIRMED')");
    $stmt->execute([$id, $userId, $parkingId, $start, $end]);
  }
}
