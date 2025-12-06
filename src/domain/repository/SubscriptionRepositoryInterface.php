<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Abonnement;
use App\Domain\Entity\User;

interface SubscriptionRepositoryInterface
{
    /**
     * Sauvegarde un nouvel abonnement.
     */
    public function save(Abonnement $abonnement): void;

    /**
     * Récupère les abonnements actifs d'un utilisateur.
     * @return Abonnement[]
     */
    public function findActiveByUser(User $user): array;
}