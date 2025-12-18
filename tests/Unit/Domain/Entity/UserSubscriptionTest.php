<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\UserSubscription;
use App\Domain\ValueObject\WeeklySchedule;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UserSubscriptionTest extends TestCase
{
  /**
   * Teste les Getters et la construction standard
   */
  public function testItCanBeCreatedAndGettersWork(): void
  {
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-12-31');
    // Un horaire vide = Ouvert tout le temps selon ton VO
    $schedule = new WeeklySchedule([]);

    $subscription = new UserSubscription(
      'sub-123',
      'user-1',
      'park-1',
      'plan-gold',
      $start,
      $end,
      $schedule,
      true
    );

    $this->assertEquals('sub-123', $subscription->getId());
    $this->assertEquals('user-1', $subscription->getUserId());
    $this->assertEquals('park-1', $subscription->getParkingId());
    $this->assertEquals('plan-gold', $subscription->getPlanId());
    $this->assertSame($start, $subscription->getStartDate());
    $this->assertSame($end, $subscription->getEndDate());
    $this->assertSame($schedule, $subscription->getSchedule());
    $this->assertTrue($subscription->isActive());
  }

  /**
   * Teste la logique combinée (Dates + Horaires)
   */
  public function testOccupiesSpotRespectsScheduleAndDates(): void
  {
    // ARRANGE : On crée un "Forfait Nuit" (18h00 -> 08h00 le lendemain)
    // Lundi soir (J1) au Mardi matin (J2)
    $nightConfig = [
      ['startDay' => 1, 'startTime' => '18:00', 'endDay' => 2, 'endTime' => '08:00']
    ];
    $nightSchedule = new WeeklySchedule($nightConfig);

    $start = new DateTimeImmutable('2024-01-01'); // Début contrat
    $end = new DateTimeImmutable('2024-01-31');   // Fin contrat

    $sub = new UserSubscription('s1', 'u1', 'p1', 'plan-nuit', $start, $end, $nightSchedule);

    // CAS 1 : C'est OUI (Lundi 1er Janvier à 20h00 - Dans les dates ET dans l'horaire nuit)
    $mondayNight = new DateTimeImmutable('2024-01-01 20:00');
    $this->assertTrue($sub->occupiesSpotAt($mondayNight), "Devrait occuper une place le lundi soir (Nuit)");

    // CAS 2 : C'est NON (Lundi 1er Janvier à 12h00 - Dans les dates MAIS hors horaire)
    $mondayNoon = new DateTimeImmutable('2024-01-01 12:00');
    $this->assertFalse($sub->occupiesSpotAt($mondayNoon), "Ne devrait PAS occuper de place à midi (Hors forfait Nuit)");

    // CAS 3 : C'est NON (Hors Dates contrat)
    // Le 15 Février à 20h00 (L'horaire est bon, mais l'abo est fini)
    $expiredDate = new DateTimeImmutable('2024-02-15 20:00');
    $this->assertFalse($sub->occupiesSpotAt($expiredDate), "Ne devrait pas occuper de place après la fin de l'abonnement");
  }

  public function testItDoesNotOccupySpotIfInactive(): void
  {
    // Un abonnement valide tout le temps...
    $schedule = new WeeklySchedule([]); // Vide = 24/7
    $start = new DateTimeImmutable('2024-01-01');
    $end = new DateTimeImmutable('2024-12-31');

    // ... MAIS désactivé (isActive = false)
    $sub = new UserSubscription('s1', 'u1', 'p1', 'pA', $start, $end, $schedule, false);

    $this->assertFalse($sub->occupiesSpotAt(new DateTimeImmutable('2024-06-01')), "Un abonnement inactif ne doit jamais prendre de place");
  }

  public function testConstructorValidation(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("La fin de l'abonnement doit être après le début.");

    $start = new DateTimeImmutable('2024-01-31');
    $end = new DateTimeImmutable('2024-01-01'); // Fin AVANT début

    new UserSubscription('id', 'u', 'p', 'pl', $start, $end, new WeeklySchedule([]));
  }
}
