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
    $nightRule = new WeeklySchedule([
      ['startDay' => 1, 'startTime' => '18:00', 'endDay' => 2, 'endTime' => '08:00']
    ]);

    $id = "plan_123";
    $name = "Forfait Nuit";
    $price = 5000;

    // 2. ACTION
    $plan = new SubscriptionPlan($id, $name, $price, $nightRule);

    // 3. ASSERTION
    $this->assertEquals($id, $plan->getId());
    $this->assertEquals($name, $plan->getName());
    $this->assertEquals($price, $plan->getMonthlyPrice());
    $this->assertSame($nightRule, $plan->getSchedule());
  }

  public function testToArrayReturnsSerializableFormat(): void
  {
    // 1. ARRANGEMENT
    $scheduleConfig = [
      ['startDay' => 5, 'startTime' => '18:00', 'endDay' => 1, 'endTime' => '08:00']
    ];
    $weekendRule = new WeeklySchedule($scheduleConfig);

    $plan = new SubscriptionPlan("plan_we", "Forfait Week-End", 4500, $weekendRule);

    // 2. ACTION
    $arrayResult = $plan->toArray();

    // 3. ASSERTION
    $expected = [
      'id' => "plan_we",
      'name' => "Forfait Week-End",
      'price' => 4500,
      'schedule' => $scheduleConfig
    ];

    $this->assertSame($expected, $arrayResult);
  }
}
