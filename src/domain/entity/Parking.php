<?php

namespace App\Domain\Entity;

use App\Domain\ValueObject\Money;

class Parking
{
   
    
    /**
     * Calcule le prix pour une durée donnée en minutes.
     * Simulation : 1€ toutes les 15 minutes (sera remplacé par la vraie grille tarifaire).
     * 
     * @param int $minutes Durée en minutes
     * @return Money Prix calculé
     */
    public function calculatePriceForDuration(int $minutes): Money
    {
        
        $tranches = ceil($minutes / 15); 
        $prix = $tranches * 1.0; 
        
        return new Money($prix, 'EUR');
    }
}

