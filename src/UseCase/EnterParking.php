<?php

namespace App\UseCase;

use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Entity\Stationnement;
use DateTimeImmutable;

class EnterParking
{
    public function __construct(
        private StationnementRepositoryInterface $stationnementRepo,
        private ReservationRepositoryInterface $reservationRepo,
        private UserRepositoryInterface $userRepo,
        private ParkingRepositoryInterface $parkingRepo
    ) {}

    public function execute(string $userId, string $parkingId): array
    {
        $now = new DateTimeImmutable();

       
        $user = $this->userRepo->findById($userId);
        $parking = $this->parkingRepo->findById($parkingId);

        if (!$user || !$parking) {
            throw new \Exception("Utilisateur ou Parking introuvable.");
        }

       
        $reservation = $this->reservationRepo->findActiveForUser($userId, $parkingId, $now);

        if (!$reservation) {
            throw new \Exception("Accès refusé : Aucune réservation active pour ce créneau horaire.");
        }

      
        $existingSession = $this->stationnementRepo->findActiveForUser($userId, $parkingId);
        
        if ($existingSession) {
            throw new \Exception("Accès refusé : Véhicule déjà stationné dans le parking.");
        }

    
        $stationnement = new Stationnement(
            $user,
            $parking,
            $now,              
            null,               
            null,              
            $reservation->getId() 
        );

   
        $this->stationnementRepo->save($stationnement);

        return [
            'status' => 'opened',
            'entry_time' => $now->format('Y-m-d H:i:s'),
            'message' => 'Bienvenue ! Barrière ouverte.'
        ];
    }
}

