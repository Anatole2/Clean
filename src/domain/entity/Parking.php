<?php

namespace App\Domain\Entity;

use App\Domain\ValueObject\Money;

class Parking
{
   
    private const PRICE_GRID = [
        30 => 0.60,    // 30 mn: 0,60 €
        60 => 1.20,    // 1h: 1,20 €
        90 => 2.20,    // 1h30: 2,20 €
        120 => 3.20,   // 2h: 3,20 €
        150 => 4.10,   // 2h30: 4,10 €
        180 => 5.00,   // 3h: 5,00 €
        210 => 5.90,   // 3h30: 5,90 €
        240 => 6.80,   // 4h: 6,80 €
        270 => 7.70,   // 4h30: 7,70 €
        300 => 8.60,   // 5h: 8,60 €
        330 => 9.50,   // 5h30: 9,50 €
        360 => 10.00,  // 6h à 24h: 10,00 € 
    ];

  
    public function calculatePriceForDuration(int $minutes): Money
    {
        if ($minutes <= 0) {
            return new Money(0, 'EUR');
        }

      
        if ($minutes >= 360) {
            return new Money(10.00, 'EUR');
        }

     
        foreach (self::PRICE_GRID as $duration => $price) {
            if ($minutes <= $duration) {
                return new Money($price, 'EUR');
            }
        }

     
        return new Money(10.00, 'EUR');
    }
}

