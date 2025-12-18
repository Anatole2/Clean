<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\WeeklySchedule;
use DateTimeImmutable;
use InvalidArgumentException;

class UserSubscription
{
  public function __construct(
    private string $id,
    private string $userId,
    private string $parkingId,
    private string $planId,
    private DateTimeImmutable $startDate,
    private DateTimeImmutable $endDate,
    private WeeklySchedule $schedule,
    private bool $isActive = true
  ) {
    if ($endDate <= $startDate) {
      throw new InvalidArgumentException("La fin de l'abonnement doit être après le début.");
    }
  }

  /**
   * Cœur du métier : L'abonnement prend-il une place à cet instant T ?
   */
  public function occupiesSpotAt(DateTimeImmutable $time): bool
  {
    // 1. Abonnement désactivé (ex: résilié)
    if (!$this->isActive) {
      return false;
    }

    // 2. Hors des dates de contrat (ex: contrat fini le 31 janvier, on est le 1er février)
    if ($time < $this->startDate || $time > $this->endDate) {
      return false;
    }

    // 3. Hors des horaires du forfait (ex: Forfait Nuit, il est midi)
    // On délègue la complexité à ton VO WeeklySchedule
    return $this->schedule->isOpen($time);
  }

  // --- Getters (Indispensables pour les tests et l'infra) ---

  public function getId(): string
  {
    return $this->id;
  }
  public function getUserId(): string
  {
    return $this->userId;
  }
  public function getParkingId(): string
  {
    return $this->parkingId;
  }
  public function getPlanId(): string
  {
    return $this->planId;
  }
  public function getStartDate(): DateTimeImmutable
  {
    return $this->startDate;
  }
  public function getEndDate(): DateTimeImmutable
  {
    return $this->endDate;
  }
  public function getSchedule(): WeeklySchedule
  {
    return $this->schedule;
  }
  public function isActive(): bool
  {
    return $this->isActive;
  }
}
