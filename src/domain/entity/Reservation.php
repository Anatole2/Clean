<?php

namespace App\Domain\Entity;

use DateTimeImmutable;

class Reservation
{
   
    
    private DateTimeImmutable $finReservation;

    /**
     * Constructeur simplifié pour les tests
     * 
     * @param DateTimeImmutable $fin Date de fin de la réservation
     */
    public function __construct(DateTimeImmutable $fin)
    {
        $this->finReservation = $fin;
    }

    /**
     * Retourne la date de fin de la réservation
     * 
     * @return DateTimeImmutable
     */
    public function getFinReservation(): DateTimeImmutable
    {
        return $this->finReservation;
    }
}

