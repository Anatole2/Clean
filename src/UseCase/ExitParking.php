<?php

namespace App\UseCase;

use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Service\PriceCalculator;
use DateTimeImmutable;

class ExitParking
{
    public function __construct(
        private StationnementRepositoryInterface $stationnementRepo,
        private ParkingRepositoryInterface $parkingRepo,
        private ReservationRepositoryInterface $reservationRepo,
        private PriceCalculator $priceCalculator
    ) {}

    public function execute(string $userId, string $parkingId): array
    {
        
        $now = new DateTimeImmutable();

        
        $stationnement = $this->stationnementRepo->findActiveForUser($userId, $parkingId);
        
        if (!$stationnement) {
            throw new \Exception("Impossible de sortir : Aucun véhicule stationné trouvé pour cet utilisateur.");
        }

      
        $parking = $this->parkingRepo->findById($parkingId);
        
        if (!$parking) {
            throw new \Exception("Parking introuvable.");
        }

       
        $reservationId = $stationnement->getReservationId();
        if (!$reservationId) {
             
             throw new \Exception("Erreur technique : Stationnement sans réservation liée.");
        }
        $reservation = $this->reservationRepo->findById($reservationId);

        if (!$reservation) {
            throw new \Exception("Réservation introuvable.");
        }

        
        $additionalPrice = $this->priceCalculator->calculateAdditionalCost($parking, $reservation, $now);

       
        $stationnement->markAsExited($now, $additionalPrice);

      
        $this->stationnementRepo->save($stationnement);

      
        return [
            'status' => 'closed',
            'exit_time' => $now->format('Y-m-d H:i:s'),
            'total_paid' => $additionalPrice->getAmount(),
            'currency' => $additionalPrice->getCurrency(),
            'message' => $additionalPrice->getAmount() > 0 
                ? "Sortie validée. Montant débité (retard) : " . $additionalPrice->getAmount() . "€" 
                : "Sortie validée. Aucun supplément."
        ];
    }
}

