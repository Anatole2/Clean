<?php

namespace Tests\Integration\Infrastructure;

use App\Domain\Entity\UserSubscription;
use App\Domain\ValueObject\WeeklySchedule;
use App\Infrastructure\Repository\SqlUserSubscriptionRepository;
use DateTimeImmutable;
use Tests\Integration\IntegrationTestCase;

class SqlUserSubscriptionRepositoryTest extends IntegrationTestCase
{
  private SqlUserSubscriptionRepository $repo;

  protected function setUp(): void
  {
    parent::setUp();
    $this->repo = new SqlUserSubscriptionRepository($this->pdo);
  }

  public function testItSavesAndRetrievesSubscriptionWithSchedule(): void
  {
    // 1. Pré-requis
    $this->createDummyUser('u1');
    $this->createDummyParking('p1');

    // 2. Création d'un Schedule complexe
    $schedule = new WeeklySchedule([
      ['startDay' => 1, 'startTime' => '08:00', 'endDay' => 1, 'endTime' => '12:00']
    ]);

    $sub = new UserSubscription(
      'sub-1',
      'u1',
      'p1',
      'plan-A',
      new DateTimeImmutable('2024-01-01'),
      new DateTimeImmutable('2024-01-31'),
      $schedule
    );

    // 3. Sauvegarde
    $this->repo->save($sub);

    // 4. Récupération (via findActiveOverlappingRange qui fait office de find)
    $results = $this->repo->findActiveOverlappingRange(
      'p1',
      new DateTimeImmutable('2024-01-10'),
      new DateTimeImmutable('2024-01-11')
    );

    $this->assertCount(1, $results);
    $retrieved = $results[0];

    $this->assertEquals('sub-1', $retrieved->getId());
    $this->assertEquals('plan-A', $retrieved->getPlanId());

    // Vérification de la reconstruction du WeeklySchedule
    $this->assertTrue(
      $retrieved->getSchedule()->isOpen(new DateTimeImmutable('Monday 09:00')),
      "Le schedule JSON doit avoir été correctement reconstruit"
    );
  }

  public function testFindActiveOverlappingRangeFiltersCorrectly(): void
  {
    $this->createDummyUser('u1');
    $this->createDummyParking('p1');
    $dummySchedule = new WeeklySchedule([]); // 24/7

    // Abo A : Janvier (Actif)
    $subA = new UserSubscription('A', 'u1', 'p1', 'plan', new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-31'), $dummySchedule);

    // Abo B : Février (Actif)
    $subB = new UserSubscription('B', 'u1', 'p1', 'plan', new DateTimeImmutable('2024-02-01'), new DateTimeImmutable('2024-02-28'), $dummySchedule);

    // Abo C : Janvier (MAIS Inactif)
    $subC = new UserSubscription('C', 'u1', 'p1', 'plan', new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-01-31'), $dummySchedule, false);

    $this->repo->save($subA);
    $this->repo->save($subB);
    $this->repo->save($subC);

    // ACT : On cherche sur la période "15 Janvier - 15 Février"
    // On doit trouver A (finit le 31 jan) et B (commence le 1er fév).
    // On ne doit PAS trouver C (inactif).
    $results = $this->repo->findActiveOverlappingRange(
      'p1',
      new DateTimeImmutable('2024-01-15'),
      new DateTimeImmutable('2024-02-15')
    );

    $ids = array_map(fn($s) => $s->getId(), $results);

    $this->assertContains('A', $ids, "Abo A chevauche Janvier");
    $this->assertContains('B', $ids, "Abo B chevauche Février");
    $this->assertNotContains('C', $ids, "Abo C est inactif");
  }

  // --- Helpers ---
  private function createDummyUser(string $id): void
  {
    $this->pdo->exec("INSERT INTO accounts (id, email, password_hash, role) VALUES ('$id', 'test@test.com', 'hash', 'USER')");
  }
  private function createDummyParking(string $id): void
  {
    $this->pdo->exec("INSERT INTO parkings (id, owner_id, name, latitude, longitude, total_places, price_grid, opening_hours, subscription_plans) VALUES ('$id', 'owner', 'P', 0, 0, 10, '{}', '[]', '[]')");
  }
}
