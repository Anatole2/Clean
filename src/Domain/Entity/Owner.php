<?php

namespace App\Domain\Entity;

use App\Domain\Entity\Account;

class Owner extends Account
{
    private array $ownedParkings = [];

    public function __construct(string $id, string $email, string $password, string $firstName, string $lastName)
    {
        parent::__construct($id, $email, $password, $firstName, $lastName);
    }

    public function getRole(): string
    {
        return "OWNER"; 
    }

    public function getOwnedParkings(): array
    {
        return $this->ownedParkings;
    }

    public function addParking(Parking $parking): void
    {
        if (!in_array($parking, $this->ownedParkings, true)) {
            $this->ownedParkings[] = $parking;
        }
    }
}

?>