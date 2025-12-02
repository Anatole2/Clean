<?php

namespace Tests\Unit\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use App\Domain\ValueObject\SubscriptionPlan;
use App\Domain\ValueObject\WeeklySchedule;

class SubscriptionPlanTest extends TestCase
{
  public function testItCreatesAValidPlan(): void
  {
    // 1. ARRANGEMENT
    // On crée une règle "Nuit" (18h-08h)
    $nightRule = new WeeklySchedule([
      ['startDay' => 1, 'startTime' => '18:00', 'endDay' => 2, 'endTime' => '08:00']
    ]);

    $name = "Forfait Nuit";
    $price = 5000; // 50.00€

    // 2. ACTION
    $plan = new SubscriptionPlan($name, $price, $nightRule);

    // 3. ASSERTION
    $this->assertEquals($name, $plan->getName());
    $this->assertEquals($price, $plan->getMonthlyPrice());
    $this->assertSame($nightRule, $plan->getRule());
  }

  public function testToArrayReturnsSerializableFormat(): void
  {
    // 1. ARRANGEMENT
    $scheduleConfig = [
      ['startDay' => 5, 'startTime' => '18:00', 'endDay' => 1, 'endTime' => '08:00']
    ];
    $weekendRule = new WeeklySchedule($scheduleConfig);

    $plan = new SubscriptionPlan("Forfait Week-End", 4500, $weekendRule);

    // 2. ACTION
    $arrayResult = $plan->toArray();

    // 3. ASSERTION
    // On vérifie la structure exacte du tableau (utile pour le json_encode plus tard)
    $expected = [
      'name' => "Forfait Week-End",
      'price' => 4500,
      'rule' => $scheduleConfig // Vérifie que le WeeklySchedule a bien été converti en tableau
    ];

    $this->assertSame($expected, $arrayResult);
  }
}
