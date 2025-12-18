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
    $this->pdo->exec("INSERT INTO parkings (id, name, latitude, longitude, total_places, price_grid, opening_hours, subscription_plans, owner_id) 
            VALUES ('p1', 'Parking Test', 0, 0, 10, '{}', '{}', '[]', 'u1')");
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
}
