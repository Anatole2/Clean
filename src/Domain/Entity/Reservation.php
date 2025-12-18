<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;

class Reservation
{
  public const STATUS_PENDING = 'PENDING';
  public const STATUS_CONFIRMED = 'CONFIRMED';
  public const STATUS_CANCELLED = 'CANCELLED';

  public function __construct(
    private string $id,
    private string $userId,
    private string $parkingId,
    private DateTimeImmutable $startTime,
    private DateTimeImmutable $endTime,
    private int $pricePaidInCents,
    private string $status = self::STATUS_CONFIRMED
  ) {
    if ($endTime <= $startTime) {
      throw new InvalidArgumentException("La date de fin doit être postérieure à la date de début.");
    }
    if ($pricePaidInCents < 0) {
      throw new InvalidArgumentException("Le prix ne peut pas être négatif.");
    }
  }

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
  public function getStartTime(): DateTimeImmutable
  {
    return $this->startTime;
  }
  public function getEndTime(): DateTimeImmutable
  {
    return $this->endTime;
  }
  public function getPricePaidInCents(): int
  {
    return $this->pricePaidInCents;
  }
  public function getStatus(): string
  {
    return $this->status;
  }

  public function cancel(): void
  {
    $this->status = self::STATUS_CANCELLED;
  }
}
