<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Parking;

interface ParkingRepositoryInterface
{
    public function findById(string $id): ?Parking;
    
   
    public function getTotalSpots(string $id): int;
}

