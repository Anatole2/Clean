<?php

namespace App\Domain\Entity;

use DateTimeImmutable;

class Stationnement
{
    private ?int $id;
    private User $user;
    private Parking $parking;
    private DateTimeImmutable $debutStationnement;
    private ?DateTimeImmutable $finStationnement; // Peut être null si l'utilisateur est toujours garé

    /**
     * @param User $user L'utilisateur garé.
     * @param Parking $parking Le parking utilisé.
     * @param DateTimeImmutable $debutStationnement L'heure d'entrée réelle.
     * @param DateTimeImmutable|null $finStationnement L'heure de sortie réelle (null tant que l'utilisateur n'est pas sorti).
     * @param int|null $id L'ID du stationnement.
     */
    public function __construct(
        User $user,
        Parking $parking,
        DateTimeImmutable $debutStationnement,
        ?DateTimeImmutable $finStationnement = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->user = $user;
        $this->parking = $parking;
        $this->debutStationnement = $debutStationnement;
        $this->finStationnement = $finStationnement;
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

    public function getDebutStationnement(): DateTimeImmutable
    {
        return $this->debutStationnement;
    }

    public function getFinStationnement(): ?DateTimeImmutable
    {
        return $this->finStationnement;
    }

    // Méthode Setter pour l'heure de sortie
    public function markAsExited(DateTimeImmutable $finStationnement): void
    {
        $this->finStationnement = $finStationnement;
    }

    // Exemple de logique métier
    public function isCurrentlyParked(): bool
    {
        return $this->finStationnement === null;
    }
}