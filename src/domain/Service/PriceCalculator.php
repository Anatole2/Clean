<?php

namespace App\Domain\Service;

use App\Domain\Entity\Parking;
use App\Domain\Entity\Reservation;
use App\Domain\ValueObject\Money;
use DateTimeImmutable;

class PriceCalculator
{
    private const PENALTY_AMOUNT = 20.0; 

    
    public function calculateAdditionalCost(
        Parking $parking,
        Reservation $reservation,
        DateTimeImmutable $actualExitTime
    ): Money {
       
        $reservedEndTime = $reservation->getFinReservation();
        
        $totalAmount = 0.0;

       
        if ($actualExitTime <= $reservedEndTime) {
            return new Money(0, 'EUR');
        }

        
        $totalAmount += self::PENALTY_AMOUNT;

       
        $secondsOver = $actualExitTime->getTimestamp() - $reservedEndTime->getTimestamp();
        $minutesOver = (int) ceil($secondsOver / 60); 

        
        if ($minutesOver > 0) {
            
            $additionalCost = $parking->calculatePriceForDuration($minutesOver);
            
            $totalAmount += $additionalCost->getAmount();
        }

        return new Money($totalAmount, 'EUR');
    }
}

