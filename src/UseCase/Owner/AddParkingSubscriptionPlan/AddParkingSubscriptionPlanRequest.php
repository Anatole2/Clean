<?php

declare(strict_types=1);

namespace App\UseCase\Owner\AddParkingSubscriptionPlan;

class AddParkingSubscriptionPlanRequest
{
  public function __construct(
    public string $parkingId,
    public string $ownerId,       // Pour la sécurité (Vérification propriétaire)
    public string $planName,      // ex: "Forfait Nuit"
    public int $monthlyPrice,     // ex: 5000 (50.00€)
    public array $ruleConfig      // Le JSON brut pour le WeeklySchedule
  ) {}
}
