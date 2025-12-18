<?php

namespace Tests\Integration\Infrastructure;

use App\Domain\Entity\Reservation;
use App\Infrastructure\Repository\SqlReservationRepository;
use DateTimeImmutable;
use Tests\Integration\IntegrationTestCase;

class SqlReservationRepositoryTest extends IntegrationTestCase
{
  private SqlReservationRepository $repo;

  protected function setUp(): void
  {
    parent::setUp();
    $this->repo = new SqlReservationRepository($this->pdo);
  }

  public function testItSavesAndCountsOverlappingReservations(): void
  {
    // 1. Pré-requis : Créer un User et un Parking (pour les Clés Étrangères)
    $this->createDummyUser('u1');
    $this->createDummyParking('p1');

    // 2. Création d'une réservation de référence : 14h00 à 16h00
    $start = new DateTimeImmutable('2024-01-01 14:00:00');
    $end   = new DateTimeImmutable('2024-01-01 16:00:00');

    $reservation = new Reservation('res-1', 'u1', 'p1', $start, $end, 500);
    $this->repo->save($reservation);

    // 3. VÉRIFICATION DU COMPTEUR (La logique Overlap)

    // CAS A : Pas de chevauchement (Avant : 12h-14h)
    // Note: Si je finis à 14h00 pile et que l'autre commence à 14h00 pile, ça ne touche pas.
    $count = $this->repo->countOverlappingReservations(
      'p1',
      new DateTimeImmutable('2024-01-01 12:00:00'),
      new DateTimeImmutable('2024-01-01 14:00:00')
    );
    $this->assertEquals(0, $count, "Ne devrait pas compter la réservation d'avant");

    // CAS B : Chevauchement Total (Dedans : 14h30-15h30)
    $count = $this->repo->countOverlappingReservations(
      'p1',
      new DateTimeImmutable('2024-01-01 14:30:00'),
      new DateTimeImmutable('2024-01-01 15:30:00')
    );
    $this->assertEquals(1, $count, "Devrait trouver la réservation (inclus)");

    // CAS C : Chevauchement Partiel (Début : 13h00-15h00)
    $count = $this->repo->countOverlappingReservations(
      'p1',
      new DateTimeImmutable('2024-01-01 13:00:00'),
      new DateTimeImmutable('2024-01-01 15:00:00')
    );
    $this->assertEquals(1, $count, "Devrait trouver la réservation (overlap début)");

    // CAS D : Chevauchement Partiel (Fin : 15h00-17h00)
    $count = $this->repo->countOverlappingReservations(
      'p1',
      new DateTimeImmutable('2024-01-01 15:00:00'),
      new DateTimeImmutable('2024-01-01 17:00:00')
    );
    $this->assertEquals(1, $count, "Devrait trouver la réservation (overlap fin)");

    // CAS E : Un autre parking
    $this->createDummyParking('p2');
    $count = $this->repo->countOverlappingReservations(
      'p2', // ID différent
      new DateTimeImmutable('2024-01-01 14:30:00'),
      new DateTimeImmutable('2024-01-01 15:30:00')
    );
    $this->assertEquals(0, $count, "Ne devrait pas compter les réservations d'un autre parking");
  }

  public function testItIgnoresCancelledReservations(): void
  {
    $this->createDummyUser('u1');
    $this->createDummyParking('p1');

    $start = new DateTimeImmutable('2024-01-01 14:00:00');
    $end   = new DateTimeImmutable('2024-01-01 16:00:00');

    // On crée une réservation annulée
    $reservation = new Reservation('res-cancel', 'u1', 'p1', $start, $end, 0, Reservation::STATUS_CANCELLED);
    $this->repo->save($reservation);

    $count = $this->repo->countOverlappingReservations('p1', $start, $end);

    $this->assertEquals(0, $count, "Une réservation annulée ne doit pas compter comme place occupée");
  }

  // --- Helpers SQL rapides ---
  private function createDummyUser(string $id): void
  {
    $this->pdo->exec("INSERT INTO accounts (id, email, password_hash, role) VALUES ('$id', 'test@test.com', 'hash', 'USER')");
  }
  private function createDummyParking(string $id): void
  {
    $this->pdo->exec("INSERT INTO parkings (id, owner_id, name, latitude, longitude, total_places, price_grid, opening_hours, subscription_plans) VALUES ('$id', 'owner', 'P', 0, 0, 10, '{}', '[]', '[]')");
  }
}
