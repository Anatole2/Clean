<?php

namespace Tests\Domain\Service;

use PHPUnit\Framework\TestCase;
use App\Domain\Service\PriceCalculator;
use App\Domain\Entity\Parking;
use App\Domain\Entity\Reservation;
use DateTimeImmutable;

class PriceCalculatorTest extends TestCase
{
    public function testNoPenaltyWhenExitingOnTime()
    {
      
        $calculator = new PriceCalculator();
        $parking = new Parking(); 
        
      
        $finReservation = new DateTimeImmutable('2025-01-01 14:00:00');
        $reservation = new Reservation($finReservation);

       
        $exitTime = new DateTimeImmutable('2025-01-01 14:00:00');
        $coutSupplementaire = $calculator->calculateAdditionalCost($parking, $reservation, $exitTime);

       
        $this->assertEquals(0.0, $coutSupplementaire->getAmount());
    }

    public function testPenaltyAppliedWhenLate()
    {
       
        $calculator = new PriceCalculator();
        $parking = new Parking(); 
        
        
        $finReservation = new DateTimeImmutable('2025-01-01 14:00:00');
        $reservation = new Reservation($finReservation);

        
        $exitTime = new DateTimeImmutable('2025-01-01 15:00:00');
        $coutSupplementaire = $calculator->calculateAdditionalCost($parking, $reservation, $exitTime);

       
        $this->assertEquals(21.20, $coutSupplementaire->getAmount(), "Le prix devrait être de 21,20€ (20€ pénalité + 1,20€ durée selon grille VINCI PARK)");
    }
}

