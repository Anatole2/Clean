<?php

declare(strict_types=1);

namespace App\UseCase\User\GenerateInvoice;

class InvoiceDto
{
  public function __construct(
    public string $invoiceNumber,   // Ex: INV-2025-0001
    public string $createdAt,       // Date d'émission

    // Vendeur (Parking)
    public string $sellerName,
    public string $sellerAddress,   // (On mettra lat/lon ou une fausse adresse pour l'instant)

    // Acheteur (User)
    public string $buyerName,
    public string $buyerEmail,

    // Détails
    public string $description,     // Ex: "Réservation Parking Test..."
    public string $startAt,
    public string $endAt,
    public float $amountExclTax,    // Montant HT
    public float $vatAmount,        // Montant TVA (20%)
    public float $totalAmount       // Montant TTC
  ) {}
}
