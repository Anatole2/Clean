<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure;

use App\Domain\Entity\UserSubscription;
use App\Domain\ValueObject\WeeklySchedule;
use App\Infrastructure\Repository\SqlUserSubscriptionRepository;
use Tests\Integration\IntegrationTestCase;
use DateTimeImmutable;

class SqlUserSubscriptionRepositoryTest extends IntegrationTestCase
{
  private SqlUserSubscriptionRepository $repo;

  protected function setUp(): void
  {
    parent::setUp();
    $this->repo = new SqlUserSubscriptionRepository($this->pdo);
    $this->createDummyData();
  }

  public function testSaveAndFindOverlapping(): void
  {
    // Test basique existant
    $schedule = new WeeklySchedule([]); // Vide = H24
    $sub = new UserSubscription(
      'sub-1',
      'u1',
      'p1',
      'plan-1',
      new DateTimeImmutable('2025-01-01'),
      new DateTimeImmutable('2025-01-31'),
      $schedule,
      true
    );

    $this->repo->save($sub);

    $found = $this->repo->findActiveOverlappingRange('p1', new DateTimeImmutable('2025-01-05'), new DateTimeImmutable('2025-01-06'));
    $this->assertCount(1, $found);
  }

  // 👇 NOUVEAU TEST : Vérifie le filtrage précis des jours
  public function testFindActiveForUserRespectsWeeklySchedule(): void
  {
    // ARRANGE
    // Abonnement valable tout Janvier 2025
    // MAIS uniquement le LUNDI (Day 1) de 08h à 18h
    $scheduleData = [
      ['startDay' => 1, 'startTime' => '08:00', 'endDay' => 1, 'endTime' => '18:00']
    ];
    $schedule = new WeeklySchedule($scheduleData);

    $sub = new UserSubscription(
      'sub-monday',
      'u1',
      'p1',
      'plan-1',
      new DateTimeImmutable('2025-01-01 00:00'),
      new DateTimeImmutable('2025-01-31 23:59'),
      $schedule,
      true
    );
    $this->repo->save($sub);

    // CAS 1 : On cherche un Lundi à 10h (Le 6 Janvier 2025 est un Lundi)
    $mondayMorning = new DateTimeImmutable('2025-01-06 10:00:00');
    $found = $this->repo->findActiveForUser('u1', 'p1', $mondayMorning);

    $this->assertNotNull($found, "Devrait trouver l'abo car c'est un Lundi matin");
    $this->assertEquals('sub-monday', $found->getId());

    // CAS 2 : On cherche un Lundi SOIR (Hors horaires)
    $mondayNight = new DateTimeImmutable('2025-01-06 20:00:00');
    $notFoundTime = $this->repo->findActiveForUser('u1', 'p1', $mondayNight);
    $this->assertNull($notFoundTime, "Ne devrait pas trouver car hors des heures du lundi");

    // CAS 3 : On cherche un MARDI (Le 7 Janvier 2025)
    $tuesday = new DateTimeImmutable('2025-01-07 10:00:00');
    $notFoundDay = $this->repo->findActiveForUser('u1', 'p1', $tuesday);
    $this->assertNull($notFoundDay, "Ne devrait pas trouver car ce n'est pas un lundi");
  }

  // 👇 NOUVEAU TEST : Vérifie le flag is_active
  public function testFindActiveForUserReturnsNullIfInactive(): void
  {
    $schedule = new WeeklySchedule([]); // H24
    $sub = new UserSubscription(
      'sub-inactive',
      'u1',
      'p1',
      'plan-1',
      new DateTimeImmutable('2025-01-01'),
      new DateTimeImmutable('2025-01-31'),
      $schedule,
      false // ❌ Inactif
    );
    $this->repo->save($sub);

    $now = new DateTimeImmutable('2025-01-10 12:00');
    $found = $this->repo->findActiveForUser('u1', 'p1', $now);

    $this->assertNull($found);
  }

  private function createDummyData(): void
  {
    $this->pdo->exec("INSERT INTO accounts (id, email, password_hash, role) VALUES ('u1', 'test@test.com', 'hash', 'USER')");
    $this->pdo->exec("INSERT INTO parkings (id, name, latitude, longitude, total_places, price_grid, opening_hours, subscription_plans, owner_id) 
            VALUES ('p1', 'Parking Test', 0, 0, 10, '{}', '{}', '[]', 'u1')");
  }
}
