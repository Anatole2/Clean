<?php

declare(strict_types=1);

namespace App\UseCase\User\GenerateInvoice;

class InvoiceDto
{
  public function __construct(
    public string $invoiceNumber,
    public string $createdAt,

    // Vendeur (Parking)
    public string $sellerName,
    public string $sellerAddress,

    // Acheteur (User)
    public string $buyerName,
    public string $buyerEmail,

    // Détails
    public string $description,
    public string $startAt,
    public string $endAt,
    public float $amountExclTax,
    public float $vatAmount,
    public float $totalAmount
  ) {}
}
