<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;
use DateTimeImmutable;

class Parking
{
  public function __construct(
    private string $id,
    private string $ownerId,
    private string $name,
    private GpsCoordinates $coordinates,
    private int $totalPlaces,
    private PriceGrid $priceGrid,
    private WeeklySchedule $openingHours
  ) {}

  // --- Logique Métier (Délégation aux Value Objects) ---

  public function isOpen(DateTimeImmutable $time): bool
  {
    return $this->openingHours->isOpen($time);
  }

  public function calculatePrice(int $durationInMinutes): int
  {
    return $this->priceGrid->calculatePrice($durationInMinutes);
  }

  // --- Getters (Pour l'accès aux données) ---

  public function getId(): string
  {
    return $this->id;
  }

  public function getOwnerId(): string
  {
    return $this->ownerId;
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function getCoordinates(): GpsCoordinates
  {
    return $this->coordinates;
  }

  public function getTotalPlaces(): int
  {
    return $this->totalPlaces;
  }

  public function getPriceGrid(): PriceGrid
  {
    return $this->priceGrid;
  }

  public function getOpeningHours(): WeeklySchedule
  {
    return $this->openingHours;
  }
}
