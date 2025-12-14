<?php

namespace App\Domain\Entity;

use DateTimeImmutable;

class Reservation
{
    private ?int $id;
    private DateTimeImmutable $finReservation;

    public function __construct(DateTimeImmutable $fin, ?int $id = null)
    {
        $this->finReservation = $fin;
        $this->id = $id;
    }

    public function getFinReservation(): DateTimeImmutable
    {
        return $this->finReservation;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}

