<?php

declare(strict_types=1);

namespace App\UseCase\Owner\UpdateParkingHours;

class UpdateParkingHoursRequest
{
  public function __construct(
    public string $parkingId,
    public string $ownerId,
    public array $newOpeningHoursConfig // Le JSON brut des horaires
  ) {}
}
