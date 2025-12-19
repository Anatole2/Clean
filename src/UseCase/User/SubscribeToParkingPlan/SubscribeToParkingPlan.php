<?php

declare(strict_types=1);

namespace App\UseCase\User\SubscribeToParkingPlan;

use App\Domain\Entity\UserSubscription;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\Infrastructure\Service\RamseyIdGenerator;
use DateTimeImmutable;
use Exception;

class SubscribeToParkingPlan
{
  public function __construct(
    private ParkingRepositoryInterface $parkingRepository,
    private UserSubscriptionRepositoryInterface $subscriptionRepository,
    private RamseyIdGenerator $idGenerator
  ) {}

  public function execute(SubscribeToParkingPlanRequest $request): SubscribeToParkingPlanResponse
  {
    // 1. Récupérer le parking
    $parking = $this->parkingRepository->findById($request->parkingId);
    if (!$parking) {
      throw new Exception("Parking introuvable.");
    }

    // 2. Trouver le plan demandé (par son nom)
    $selectedPlan = null;
    foreach ($parking->getSubscriptionPlans() as $plan) {
      if ($plan->getId() === $request->planId) {
        $selectedPlan = $plan;
        break;
      }
    }

    if (!$selectedPlan) {
      throw new Exception("Le plan d'abonnement '{$request->planId}' n'existe pas pour ce parking.");
    }

    // 3. Calcul des dates
    $startDate = DateTimeImmutable::createFromFormat('Y-m-d', $request->startDate);
    if (!$startDate) {
      throw new Exception("Format de date invalide (attendu: YYYY-MM-DD).");
    }

    $startDate = $startDate->setTime(0, 0, 0); // Début de journée
    $today = new DateTimeImmutable('today');

    if ($startDate < $today) {
      throw new Exception("La date de début ne peut pas être dans le passé.");
    }

    // Durée : 1 mois (règle métier par défaut)
    // La fin est à 23:59:59 du dernier jour
    $endDate = $startDate->modify('+1 month')->modify('-1 second');

    // 4. Vérification de Capacité
    // On vérifie combien d'abonnements sont déjà actifs sur cette période
    $activeSubs = $this->subscriptionRepository->countActiveForParking(
      $parking->getId(),
      $startDate,
      $endDate
    );

    // Règle simplifiée : Si nb_abonnés >= nb_places, on refuse.
    // (On pourrait faire plus complexe en mélangeant avec les réservations, 
    // mais c'est une sécurité de base pour éviter la survente massive).
    if ($activeSubs >= $parking->getTotalPlaces()) {
      throw new Exception("Impossible de souscrire : Le quota d'abonnements pour ce parking est atteint.");
    }

    // 5. Création de l'entité UserSubscription
    $subscription = new UserSubscription(
      $this->idGenerator->generate(),
      $request->userId,
      $parking->getId(),
      $selectedPlan->getId(),       // 1. L'ID du plan
      $selectedPlan->getName(),     // 2. Le Nom (Snapshot)
      $selectedPlan->getMonthlyPrice(), // 3. Le Prix (Snapshot)
      $startDate,
      $endDate,
      $selectedPlan->getSchedule()  // 4. Les Horaires (Snapshot)
    );

    // 6. Sauvegarde
    $this->subscriptionRepository->save($subscription);

    return new SubscribeToParkingPlanResponse($subscription);
  }
}
