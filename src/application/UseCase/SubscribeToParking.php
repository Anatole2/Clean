<?php

namespace App\Application\UseCase;

use App\Domain\Entity\Abonnement;
use App\Domain\Entity\User;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\SubscriptionRepositoryInterface;
use App\Domain\Service\AvailabilityCheckerInterface;

class SubscribeToParking
{
    private ParkingRepositoryInterface $parkingRepo;
    private SubscriptionRepositoryInterface $subscriptionRepo;
    private AvailabilityCheckerInterface $availabilityChecker;
    // (Note : On aura peut-être besoin d'un UserRepository ici aussi)

    public function __construct(
        ParkingRepositoryInterface $parkingRepo,
        SubscriptionRepositoryInterface $subscriptionRepo,
        AvailabilityCheckerInterface $availabilityChecker
    ) {
        $this->parkingRepo = $parkingRepo;
        $this->subscriptionRepo = $subscriptionRepo;
        $this->availabilityChecker = $availabilityChecker;
    }

    /**
     * @param User $user L'utilisateur qui s'abonne.
     * @param int $parkingId L'ID du parking.
     * @param \DateTimeImmutable $dateDebut
     * @param \DateTimeImmutable $dateFin
     * @param array $creneauxHebdomadaires Le JSON/Array des horaires récurrents.
     */
    public function execute(
        User $user,
        int $parkingId,
        \DateTimeImmutable $dateDebut,
        \DateTimeImmutable $dateFin,
        array $creneauxHebdomadaires
    ): void {
        $parking = $this->parkingRepo->findById($parkingId);

        if (!$parking) {
            throw new \App\Domain\Exception\ParkingNotFoundException();
        }

        // 1. Logique critique : Vérifier si le parking est disponible pour TOUS les créneaux.
        // NOTE: Cette vérification peut être plus complexe et faire partie de l'AvailabilityChecker
        // pour s'assurer que l'abonnement ne dépasse pas la capacité du parking sur les créneaux demandés.
        
        // Exemple simple : on suppose que la disponibilité est validée par un service
        $isAvailable = $this->availabilityChecker->checkSubscriptionAvailability($parking, $creneauxHebdomadaires, $dateDebut, $dateFin);
        
        if (!$isAvailable) {
             throw new \App\Domain\Exception\SubscriptionNotAvailableException();
        }

        // 2. Calcul du prix (peut nécessiter une grille tarifaire spécifique pour les abonnements)
        $montant = $parking->calculateSubscriptionPrice($creneauxHebdomadaires, $dateDebut, $dateFin); 

        // 3. Création et Sauvegarde de l'Entité
        $abonnement = new Abonnement($user, $parking, $dateDebut, $dateFin, $creneauxHebdomadaires, $montant);
        $this->subscriptionRepo->save($abonnement);
    }
}