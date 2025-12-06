<?php

namespace App\Domain\Entity;

use DateTimeImmutable;
use App\domain\ValueObject\Money; 

class Stationnement
{
    private ?int $id;
    private User $user;
    private Parking $parking;
    private DateTimeImmutable $debutStationnement;
    private ?DateTimeImmutable $finStationnement;

    
    private ?Money $pricePaid = null; 
    private ?int $reservationId = null; 

    public function __construct(
        User $user,
        Parking $parking,
        DateTimeImmutable $debutStationnement,
        ?DateTimeImmutable $finStationnement = null,
        ?int $id = null,
        ?int $reservationId = null 
    ) {
        $this->id = $id;
        $this->user = $user;
        $this->parking = $parking;
        $this->debutStationnement = $debutStationnement;
        $this->finStationnement = $finStationnement;
        $this->reservationId = $reservationId;
    }


    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getParking(): Parking { return $this->parking; }
    public function getDebutStationnement(): DateTimeImmutable { return $this->debutStationnement; }
    public function getFinStationnement(): ?DateTimeImmutable { return $this->finStationnement; }

   
    public function getPricePaid(): ?Money { return $this->pricePaid; }
    public function getReservationId(): ?int { return $this->reservationId; }
   
    public function markAsExited(DateTimeImmutable $finStationnement, Money $price): void
    {
       
        if ($finStationnement < $this->debutStationnement) {
            throw new \Exception("La sortie ne peut pas être avant l'entrée");
        }

        $this->finStationnement = $finStationnement;
        $this->pricePaid = $price;
    }

    public function isCurrentlyParked(): bool
    {
        return $this->finStationnement === null;
    }
}