<?php

namespace App\Application\UseCase;

use App\Domain\Entity\User;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\SubscriptionRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;

class ViewUserHistory
{
    private ReservationRepositoryInterface $reservationRepo;
    private SubscriptionRepositoryInterface $subscriptionRepo;
    private StationnementRepositoryInterface $stationnementRepo;

    public function __construct(
        ReservationRepositoryInterface $reservationRepo,
        SubscriptionRepositoryInterface $subscriptionRepo,
        StationnementRepositoryInterface $stationnementRepo
    ) {
        $this->reservationRepo = $reservationRepo;
        $this->subscriptionRepo = $subscriptionRepo;
        $this->stationnementRepo = $stationnementRepo;
    }

    /**
     * Récupère l'historique complet d'un utilisateur.
     * @return array<string, array>
     */
    public function execute(User $user): array
    {
        // 1. Récupérer les données de chaque source
        $reservations = $this->reservationRepo->findByUser($user);
        $subscriptions = $this->subscriptionRepo->findActiveByUser($user);
        $stationnements = $this->stationnementRepo->findCompletedByUser($user);
        
        // 2. Assembler les données (éventuellement les trier par date/heure)
        $history = [
            'reservations' => $reservations,
            'subscriptions' => $subscriptions,
            'parkings' => $stationnements, // Renommé 'parkings' ou 'stationnements' pour la clarté
        ];
        
        // 3. Retourner le résultat pour la présentation (Couche Présentation/Controller)
        return $history;
    }
}