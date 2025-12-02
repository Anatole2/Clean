<?php

namespace App\Domain\Entity;

use App\Domain\Entity\Account;

class User extends Account
{
    private array $reservations = [];
    private array $stationnements = []; // Liste des stationnements dans les parkings

    public function __construct(string $id, string $email, string $password, string $firstName, string $lastName)
    {
        parent::__construct($id, $email, $password, $firstName, $lastName);
    }

    public function addReservation(Reservation $reservation): void
    {
        $this->reservations[] = $reservation;
    }

    public function addStationnement(Stationnement $stationnement): void
    {
        $this->stationnements[] = $stationnement;
    }

    public function getReservations(): array
    {
        return $this->reservations;
    }

    public function getStationnements(): array
    {
        return $this->stationnements;
    }
    
    public function getRole(): string
    {
        return "DRIVER"; 
    }
}

?>