<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

class ParkingSession
{
  public function __construct(
    private string $id,
    private string $parkingId,
    private string $userId,
    private ?string $reservationId,
    private DateTimeImmutable $entryTime,
    private ?DateTimeImmutable $exitTime = null,
    private int $pricePaid = 0
  ) {}

  // Une session est "en cours" si la voiture n'est pas sortie
  public function isOngoing(): bool
  {
    return $this->exitTime === null;
  }

  // Pour faire sortir la voiture
  public function close(DateTimeImmutable $exitTime, int $finalPrice): void
  {
    if ($exitTime < $this->entryTime) {
      throw new \InvalidArgumentException("La date de sortie ne peut pas être avant la date d'entrée.");
    }
    $this->exitTime = $exitTime;
    $this->pricePaid = $finalPrice;
  }

  // Getters
  public function getId(): string
  {
    return $this->id;
  }
  public function getParkingId(): string
  {
    return $this->parkingId;
  }
  public function getUserId(): string
  {
    return $this->userId;
  }
  public function getReservationId(): ?string
  {
    return $this->reservationId;
  }
  public function getEntryTime(): DateTimeImmutable
  {
    return $this->entryTime;
  }
  public function getExitTime(): ?DateTimeImmutable
  {
    return $this->exitTime;
  }
  public function getPricePaid(): int
  {
    return $this->pricePaid;
  }
}
