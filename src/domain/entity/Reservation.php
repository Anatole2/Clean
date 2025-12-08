<?php

namespace App\Domain\Entity;

use DateTimeImmutable;

class Reservation
{
   
    
    private DateTimeImmutable $finReservation;

  
    public function __construct(DateTimeImmutable $fin)
    {
        $this->finReservation = $fin;
    }

    public function getFinReservation(): DateTimeImmutable
    {
        return $this->finReservation;
    }
}

