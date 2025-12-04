<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Reservation;
use App\Domain\Entity\User;

interface ReservationRepositoryInterface
{
    /**
     * Sauvegarde une nouvelle réservation.
     */
    public function save(Reservation $reservation): void;

    /**
     * Récupère toutes les réservations d'un utilisateur (pour l'historique).
     * @return Reservation[]
     */
    public function findByUser(User $user): array;
}