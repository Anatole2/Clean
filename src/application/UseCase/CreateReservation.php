<?php

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Service\AvailabilityCheckerInterface;
// ... autres dépendances (ex: pour l'entité User)

class CreateReservation
{
    public function __construct(
        ParkingRepositoryInterface $parkingRepo,
        ReservationRepositoryInterface $reservationRepo,
        AvailabilityCheckerInterface $availabilityChecker
        // ...
    ) {
        // ...
    }
    
    // On suppose que l'ID de l'utilisateur est connu (via JWT)
    public function execute(int $userId, int $parkingId, \DateTimeImmutable $debut, \DateTimeImmutable $fin): void
    {
        // 1. Récupérer le Parking
        // 2. Vérifier la disponibilité (via AvailabilityCheckerInterface)
        // 3. Calculer le prix
        // 4. Créer l'entité Reservation
        // 5. Sauvegarder l'entité (via ReservationRepositoryInterface::save)
    }
}