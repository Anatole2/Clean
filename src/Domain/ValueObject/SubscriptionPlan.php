<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

class SubscriptionPlan
{
  public function __construct(
    private string $id,               // ex: 1
    private string $name,           // ex: "Forfait Nuit"
    private int $monthlyPrice,      // ex: 5000 (en centimes = 50.00€)
    private WeeklySchedule $schedule    // ex: Créneaux de 18h à 08h
  ) {}

  public function getId(): string
  {
    return $this->id;
  }
  public function getName(): string
  {
    return $this->name;
  }
  public function getMonthlyPrice(): int
  {
    return $this->monthlyPrice;
  }
  public function getSchedule(): WeeklySchedule
  {
    return $this->schedule;
  }

  public function toArray(): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'price' => $this->monthlyPrice,
      'schedule' => $this->schedule->toArray() // On sauvegarde aussi la règle
    ];
  }
}
