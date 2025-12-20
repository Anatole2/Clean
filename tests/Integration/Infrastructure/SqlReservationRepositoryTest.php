<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use App\Domain\Entity\Reservation;
use App\Infrastructure\Repository\SqlReservationRepository;
use Tests\Integration\IntegrationTestCase;
use DateTimeImmutable;

class SqlReservationRepositoryTest extends IntegrationTestCase
{
  private SqlReservationRepository $repo;

  protected function setUp(): void
  {
    parent::setUp();
    $this->repo = new SqlReservationRepository($this->pdo);
    $this->createDummyData();
  }

  public function testSaveAndCountOverlapping(): void
  {
    // Test existant (conservation de la logique de base)
    $start = new DateTimeImmutable('2025-01-01 10:00');
    $end = new DateTimeImmutable('2025-01-01 12:00');

    $reservation = new Reservation('res-1', 'u1', 'p1', $start, $end, 1000, 'CONFIRMED');
    $this->repo->save($reservation);

    $count = $this->repo->countOverlappingReservations('p1', $start, $end);
    $this->assertEquals(1, $count);
  }

  // 👇 NOUVEAU TEST
  public function testFindActiveForUserReturnsReservationWhenValid(): void
  {
    // ARRANGE
    // Réservation de 14h à 16h
    $start = new DateTimeImmutable('2025-01-01 14:00');
    $end   = new DateTimeImmutable('2025-01-01 16:00');
    $res = new Reservation('res-active', 'u1', 'p1', $start, $end, 500, 'CONFIRMED');
    $this->repo->save($res);

    // ACT
    // On cherche à 15h00 (au milieu)
    $now = new DateTimeImmutable('2025-01-01 15:00');
    $found = $this->repo->findActiveForUser('u1', 'p1', $now);

    // ASSERT
    $this->assertNotNull($found);
    $this->assertEquals('res-active', $found->getId());
  }

  // 👇 NOUVEAU TEST
  public function testFindActiveForUserReturnsNullIfTimeIsOutside(): void
  {
    // ARRANGE
    // Réservation de 14h à 16h
    $res = new Reservation(
      'res-out',
      'u1',
      'p1',
      new DateTimeImmutable('2025-01-01 14:00'),
      new DateTimeImmutable('2025-01-01 16:00'),
      500,
      'CONFIRMED'
    );
    $this->repo->save($res);

    // ACT & ASSERT
    // Cas 1 : Trop tôt (13:59)
    $tooEarly = $this->repo->findActiveForUser('u1', 'p1', new DateTimeImmutable('2025-01-01 13:59'));
    $this->assertNull($tooEarly, "Ne doit pas trouver si trop tôt");

    // Cas 2 : Trop tard (16:01)
    $tooLate = $this->repo->findActiveForUser('u1', 'p1', new DateTimeImmutable('2025-01-01 16:01'));
    $this->assertNull($tooLate, "Ne doit pas trouver si trop tard");
  }

  // 👇 NOUVEAU TEST
  public function testFindActiveForUserReturnsNullIfCancelled(): void
  {
    // ARRANGE
    $res = new Reservation(
      'res-cancelled',
      'u1',
      'p1',
      new DateTimeImmutable('2025-01-01 14:00'),
      new DateTimeImmutable('2025-01-01 16:00'),
      500,
      'CANCELLED' // ❌ Annulée
    );
    $this->repo->save($res);

    // ACT
    $now = new DateTimeImmutable('2025-01-01 15:00');
    $found = $this->repo->findActiveForUser('u1', 'p1', $now);

    // ASSERT
    $this->assertNull($found);
  }

  private function createDummyData(): void
  {
    $this->pdo->exec("INSERT INTO accounts (id, email, password_hash, role) VALUES ('u1', 'test@test.com', 'hash', 'USER')");

    // Parking 1
    $this->pdo->exec("INSERT INTO parkings (id, name, latitude, longitude, total_places, price_grid, opening_hours, subscription_plans, owner_id) 
            VALUES ('p1', 'Parking Test 1', 0, 0, 10, '{}', '{}', '[]', 'u1')");

    // Parking 2 (Pour tester le filtrage)
    $this->pdo->exec("INSERT INTO parkings (id, name, latitude, longitude, total_places, price_grid, opening_hours, subscription_plans, owner_id) 
            VALUES ('p2', 'Parking Test 2', 0, 0, 10, '{}', '{}', '[]', 'u1')");
  }
  public function testFindById(): void
  {
    // 1. On crée une réservation
    $start = new DateTimeImmutable('2025-01-01 10:00');
    $end = new DateTimeImmutable('2025-01-01 12:00');
    $reservation = new Reservation('res-id-123', 'u1', 'p1', $start, $end, 1000, 'CONFIRMED');

    $this->repo->save($reservation);

    // 2. On la cherche par son ID
    $found = $this->repo->findById('res-id-123');

    // 3. Vérifications
    $this->assertNotNull($found);
    $this->assertEquals('res-id-123', $found->getId());
    $this->assertEquals(1000, $found->getPricePaidInCents());

    // 4. On cherche un ID qui n'existe pas
    $notFound = $this->repo->findById('unknown-id');
    $this->assertNull($notFound);
  }
  public function testFindByUserIdReturnsSortedReservations(): void
  {
    // 1. On crée un deuxième utilisateur pour s'assurer qu'on ne mélange pas les données
    $this->pdo->exec("INSERT INTO accounts (id, email, password_hash, role) VALUES ('u2', 'other@test.com', 'hash', 'USER')");

    // 2. Création de données
    // R1: User 1, Date Future (Doit apparaitre en premier car ORDER BY start_time DESC)
    $r1 = new Reservation('r1', 'u1', 'p1', new DateTimeImmutable('2025-02-01 10:00'), new DateTimeImmutable('2025-02-01 12:00'), 500);
    $this->repo->save($r1);

    // R2: User 1, Date Passée (Doit apparaitre en deuxième)
    $r2 = new Reservation('r2', 'u1', 'p1', new DateTimeImmutable('2025-01-01 10:00'), new DateTimeImmutable('2025-01-01 12:00'), 500);
    $this->repo->save($r2);

    // R3: User 2 (Ne doit PAS apparaitre)
    $r3 = new Reservation('r3', 'u2', 'p1', new DateTimeImmutable('2025-01-01 10:00'), new DateTimeImmutable('2025-01-01 12:00'), 500);
    $this->repo->save($r3);

    // 3. Exécution
    $results = $this->repo->findByUserId('u1');

    // 4. Assertions
    $this->assertCount(2, $results);

    // Vérification de l'ordre (Décroissant par date de début)
    $this->assertEquals('r1', $results[0]->getId()); // Le plus récent (Février)
    $this->assertEquals('r2', $results[1]->getId()); // Le plus vieux (Janvier)
  }
  public function testFindByParkingIdReturnsCorrectReservationsOrderedByDateDesc(): void
  {
    // 1. ARRANGE

    // Réservation R1 : Parking P1, Date Récente (14h-16h) -> Doit être premier
    $r1 = new Reservation('res-recent', 'u1', 'p1', new DateTimeImmutable('2025-01-01 14:00'), new DateTimeImmutable('2025-01-01 16:00'), 100);
    $this->repo->save($r1);

    // Réservation R2 : Parking P1, Date Ancienne (08h-10h) -> Doit être deuxième
    $r2 = new Reservation('res-old', 'u1', 'p1', new DateTimeImmutable('2025-01-01 08:00'), new DateTimeImmutable('2025-01-01 10:00'), 100);
    $this->repo->save($r2);

    // Réservation R3 : Parking P2 (Ne doit PAS être récupérée)
    $r3 = new Reservation('res-other', 'u1', 'p2', new DateTimeImmutable('2025-01-01 10:00'), new DateTimeImmutable('2025-01-01 11:00'), 100);
    $this->repo->save($r3);



    // 2. ACT
    $reservations = $this->repo->findByParkingId('p1');

    // 3. ASSERT
    $this->assertCount(2, $reservations, "On ne doit récupérer que les réservations de p1");

    // Vérification du tri (Le plus récent en premier)
    $this->assertEquals('res-recent', $reservations[0]->getId());
    $this->assertEquals('res-old', $reservations[1]->getId());

    // Vérification que c'est bien le bon parking
    $this->assertEquals('p1', $reservations[0]->getParkingId());
  }

  public function testFindByParkingIdReturnsEmptyIfNoReservations(): void
  {
    // On cherche sur p2 (qui a été créé dans createDummyData mais n'a pas de réservation pour ce test)
    $reservations = $this->repo->findByParkingId('p2');

    $this->assertIsArray($reservations);
    $this->assertEmpty($reservations);
  }
}
