<?php

namespace App\Domain\Entity;

use DateTimeImmutable;

class Reservation
{
    private ?int $id;
    private User $user;
    private Parking $parking;
    private DateTimeImmutable $debutReservation;
    private DateTimeImmutable $finReservation;
    private float $prix; 

    /**
     * @param User $user L'utilisateur qui fait la réservation.
     * @param Parking $parking Le parking réservé.
     * @param DateTimeImmutable $debutReservation Timestamp du début de la réservation.
     * @param DateTimeImmutable $finReservation Timestamp de la fin de la réservation.
     * @param float $prix Le montant total de la réservation.
     * @param int|null $id L'ID de la réservation (null avant l'enregistrement en BDD).
     */
    public function __construct(
        User $user,
        Parking $parking,
        DateTimeImmutable $debutReservation,
        DateTimeImmutable $finReservation,
        float $prix,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->user = $user;
        $this->parking = $parking;
        $this->debutReservation = $debutReservation;
        $this->finReservation = $finReservation;
        $this->prix = $prix;
    }

    // Méthodes Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getParking(): Parking
    {
        return $this->parking;
    }

    public function getDebutReservation(): DateTimeImmutable
    {
        return $this->debutReservation;
    }

    public function getFinReservation(): DateTimeImmutable
    {
        return $this->finReservation;
    }

    public function getPrix(): float
    {
        return $this->prix;
    }

    // Exemple de logique métier dans l'entité
    public function isCompleted(): bool
    {
        // Une réservation est "terminée" si l'heure actuelle dépasse l'heure de fin
        return new DateTimeImmutable() > $this->finReservation;
    }
}