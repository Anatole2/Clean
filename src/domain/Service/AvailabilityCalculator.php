<?php

namespace App\Domain\Service;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\SubscriptionRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use DateTimeImmutable;

class AvailabilityCalculator
{
    public function __construct(
        private ParkingRepositoryInterface $parkingRepo,
        private ReservationRepositoryInterface $reservationRepo,
        private SubscriptionRepositoryInterface $subscriptionRepo,
        private StationnementRepositoryInterface $stationnementRepo
    ) {}

 
    public function isSpotAvailable(string $parkingId, DateTimeImmutable $start, DateTimeImmutable $end): bool
    {
      
        $totalSpots = $this->parkingRepo->getTotalSpots($parkingId);

   
        $reservedSpots = $this->reservationRepo->countOverlapping($parkingId, $start, $end);

    
        $subscribedSpots = $this->subscriptionRepo->countActiveForParking($parkingId, $start, $end);

       
        $activeStationnements = $this->stationnementRepo->countActiveOverlapping($parkingId, $start, $end);
        
       
        $occupiedSpots = $reservedSpots + $subscribedSpots + $activeStationnements;

        return ($totalSpots - $occupiedSpots) > 0;
    }

  
    public function getAvailableSpotsCount(string $parkingId, DateTimeImmutable $time): int
    {
       
        $totalSpots = $this->parkingRepo->getTotalSpots($parkingId);
        
        $reservedSpots = $this->reservationRepo->countOverlapping($parkingId, $time, $time);
        $subscribedSpots = $this->subscriptionRepo->countActiveForParking($parkingId, $time, $time);
        $activeStationnements = $this->stationnementRepo->countActiveOverlapping($parkingId, $time, $time);

       
        return max(0, $totalSpots - ($reservedSpots + $subscribedSpots + $activeStationnements));
    }
}

