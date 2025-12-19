<?php

declare(strict_types=1);

namespace App\UseCase\User\GenerateInvoice;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\AccountRepositoryInterface;
use App\UseCase\User\GenerateInvoice\GenerateInvoiceRequest;
use App\UseCase\User\GenerateInvoice\GenerateInvoiceResponse;
use App\UseCase\User\GenerateInvoice\InvoiceDto;

class GenerateInvoice
{
  public function __construct(
    private ReservationRepositoryInterface $reservationRepository,
    private ParkingRepositoryInterface $parkingRepository,
    private AccountRepositoryInterface $accountRepository
  ) {}

  public function execute(GenerateInvoiceRequest $request): GenerateInvoiceResponse
  {
    // 1. Utilisation de la Request
    $reservation = $this->reservationRepository->findById($request->reservationId);

    if (!$reservation) {
      throw new \Exception("Réservation introuvable.");
    }

    // 2. Vérification User
    if ($reservation->getUserId() !== $request->userId) {
      throw new \Exception("Accès interdit à cette facture.");
    }

    $user = $this->accountRepository->findById($request->userId);
    // ... (Le reste de la logique de vérification et calcul reste identique) ...

    // (Logique récupérée du message précédent...)
    $parking = $this->parkingRepository->findById($reservation->getParkingId());
    $parkingName = $parking ? $parking->getName() : 'Parking Inconnu';

    $priceTtc = $reservation->getPricePaidInCents() / 100;
    $vatRate = 0.20;
    $priceHt = $priceTtc / (1 + $vatRate);
    $vatAmount = $priceTtc - $priceHt;

    $invoiceNumber = 'INV-' . strtoupper(substr($reservation->getId(), 0, 8));

    // 3. Création du DTO de données
    $invoiceDto = new InvoiceDto(
      $invoiceNumber,
      (new \DateTimeImmutable())->format('d/m/Y'),
      $parkingName,
      "Coordonnées GPS: " . ($parking ? $parking->getCoordinates()->getLatitude() . ', ' . $parking->getCoordinates()->getLongitude() : ''),
      "Client (ID: {$request->userId})",
      $user->getEmail(),
      "Stationnement : " . $parkingName,
      $reservation->getStartTime()->format('d/m/Y H:i'),
      $reservation->getEndTime()->format('d/m/Y H:i'),
      round($priceHt, 2),
      round($vatAmount, 2),
      $priceTtc
    );

    // 4. Retour enveloppé dans la Response
    return new GenerateInvoiceResponse($invoiceDto);
  }
}
