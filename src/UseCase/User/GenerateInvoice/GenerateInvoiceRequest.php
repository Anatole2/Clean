<?php

declare(strict_types=1);

namespace App\UseCase\User\GenerateInvoice;

class GenerateInvoiceRequest
{
  public function __construct(
    public string $reservationId,
    public string $userId
  ) {}
}
