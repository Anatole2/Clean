<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

class SubscriptionPlan
{
  public function __construct(
    private string $name,           // ex: "Forfait Nuit"
    private int $monthlyPrice,      // ex: 5000 (en centimes = 50.00€)
    private WeeklySchedule $rule    // ex: Créneaux de 18h à 08h
  ) {}

  public function getName(): string
  {
    return $this->name;
  }
  public function getMonthlyPrice(): int
  {
    return $this->monthlyPrice;
  }
  public function getRule(): WeeklySchedule
  {
    return $this->rule;
  }

  public function toArray(): array
  {
    return [
      'name' => $this->name,
      'price' => $this->monthlyPrice,
      'rule' => $this->rule->toArray() // On sauvegarde aussi la règle
    ];
  }
}
