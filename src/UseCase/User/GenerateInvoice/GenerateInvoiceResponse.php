<?php

declare(strict_types=1);

namespace App\UseCase\User\GenerateInvoice;

class GenerateInvoiceResponse
{
  public function __construct(
    public InvoiceDto $invoice
  ) {}
}
