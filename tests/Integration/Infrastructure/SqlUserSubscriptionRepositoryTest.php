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
      'plan-name-1',
      5000,
      new DateTimeImmutable('2025-01-01'),
      new DateTimeImmutable('2025-01-31'),
      $schedule,
      true
    );

    $this->repo->save($sub);

    $found = $this->repo->findActiveOverlappingRange('p1', new DateTimeImmutable('2025-01-05'), new DateTimeImmutable('2025-01-06'));
    $this->assertCount(1, $found);
  }

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
      'plan-name-1',
      5000,
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

  public function testFindActiveForUserReturnsNullIfInactive(): void
  {
    $schedule = new WeeklySchedule([]); // H24
    $sub = new UserSubscription(
      'sub-inactive',
      'u1',
      'p1',
      'plan-1',
      'plan-name-1',
      5000,
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

  public function testCountActiveForParking(): void
  {
    // ARRANGE : On crée 2 abonnements qui se chevauchent sur Janvier
    $schedule = new WeeklySchedule([]); // H24

    $sub1 = new UserSubscription(
      'sub-1',
      'u1',
      'p1',
      'plan-1',
      'Plan A',
      5000,
      new DateTimeImmutable('2025-01-01'),
      new DateTimeImmutable('2025-01-31'),
      $schedule
    );

    $sub2 = new UserSubscription(
      'sub-2',
      'u2',
      'p1',
      'plan-1',
      'Plan A',
      5000,
      new DateTimeImmutable('2025-01-15'), // Commence au milieu du mois
      new DateTimeImmutable('2025-02-15'),
      $schedule
    );

    // Un abonnement inactif (ne doit pas compter)
    $subInactive = new UserSubscription(
      'sub-3',
      'u3',
      'p1',
      'plan-1',
      'Plan A',
      5000,
      new DateTimeImmutable('2025-01-01'),
      new DateTimeImmutable('2025-01-31'),
      $schedule,
      false // Inactif
    );

    $this->repo->save($sub1);
    $this->repo->save($sub2);
    $this->repo->save($subInactive);

    // ACT & ASSERT

    // Période couvrant tout Janvier : Doit trouver sub1 et sub2 (2 actifs)
    $count = $this->repo->countActiveForParking(
      'p1',
      new DateTimeImmutable('2025-01-01'),
      new DateTimeImmutable('2025-01-31')
    );
    $this->assertEquals(2, $count);

    // Période en Mars : 0 abonnement
    $countEmpty = $this->repo->countActiveForParking(
      'p1',
      new DateTimeImmutable('2025-03-01'),
      new DateTimeImmutable('2025-03-31')
    );
    $this->assertEquals(0, $countEmpty);
  }

  public function testFindByUserId(): void
  {
    $schedule = new WeeklySchedule([]);

    // Abonnement pour User 1 (Récent)
    $sub1 = new UserSubscription(
      'sub-u1-recent',
      'u1',
      'p1',
      'plan-1',
      'Plan A',
      5000,
      new DateTimeImmutable('2025-02-01'), // Février
      new DateTimeImmutable('2025-02-28'),
      $schedule
    );

    // Abonnement pour User 1 (Vieux)
    $sub2 = new UserSubscription(
      'sub-u1-old',
      'u1',
      'p1',
      'plan-1',
      'Plan A',
      5000,
      new DateTimeImmutable('2025-01-01'), // Janvier
      new DateTimeImmutable('2025-01-31'),
      $schedule
    );

    // Abonnement pour User 2 (Intrus)
    $sub3 = new UserSubscription(
      'sub-u2',
      'u2',
      'p1',
      'plan-1',
      'Plan A',
      5000,
      new DateTimeImmutable('2025-01-01'),
      new DateTimeImmutable('2025-01-31'),
      $schedule
    );

    $this->repo->save($sub1);
    $this->repo->save($sub2);
    $this->repo->save($sub3);

    // ACT
    $results = $this->repo->findByUserId('u1');

    // ASSERT
    $this->assertCount(2, $results);

    // Vérifie l'ordre (ORDER BY start_date DESC dans ta requête SQL)
    // Le plus récent (Février) doit être en premier
    $this->assertEquals('sub-u1-recent', $results[0]->getId());
    $this->assertEquals('sub-u1-old', $results[1]->getId());
  }
  private function createDummyData(): void
  {
    // Nettoyage (si nécessaire selon ta config, sinon ignore les DELETE)
    $this->pdo->exec("DELETE FROM user_subscriptions");
    $this->pdo->exec("DELETE FROM parkings");
    $this->pdo->exec("DELETE FROM accounts");

    // 1. Création de l'utilisateur principal
    $this->pdo->exec("INSERT INTO accounts (id, email, password_hash, role) VALUES ('u1', 'test@test.com', 'hash', 'USER')");

    // 2. AJOUT : Création des utilisateurs supplémentaires pour les tests
    $this->pdo->exec("INSERT INTO accounts (id, email, password_hash, role) VALUES ('u2', 'other@test.com', 'hash', 'USER')");
    $this->pdo->exec("INSERT INTO accounts (id, email, password_hash, role) VALUES ('u3', 'third@test.com', 'hash', 'USER')");

    // 3. Création du parking
    $this->pdo->exec("INSERT INTO parkings (id, name, latitude, longitude, total_places, price_grid, opening_hours, subscription_plans, owner_id) 
            VALUES ('p1', 'Parking Test', 0, 0, 10, '{}', '[]', '[]', 'u1')");
  }
}
