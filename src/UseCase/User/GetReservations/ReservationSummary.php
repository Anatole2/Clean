<?php

declare(strict_types=1);

namespace App\UseCase\User\GetReservations;

class ReservationSummary
{
  public function __construct(
    public string $id,
    public string $parkingId,
    public string $startAt,     // ex: '2025-01-01 10:00'
    public string $endAt,
    public int $price,          // En centimes
    public string $status,      // 'VALIDATED', 'CANCELLED', etc.
    public bool $canGenerateInvoice
  ) {}
}
