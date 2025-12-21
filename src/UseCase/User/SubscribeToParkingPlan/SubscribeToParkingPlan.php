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

    // 2. Trouver le plan
    $selectedPlan = null;
    foreach ($parking->getSubscriptionPlans() as $plan) {
      if ($plan->getId() === $request->planId) {
        $selectedPlan = $plan;
        break;
      }
    }
    if (!$selectedPlan) {
      throw new Exception("Le plan d'abonnement '{$request->planId}' n'existe pas.");
    }

    // 3. Gestion et Validation des Dates
    $startDate = DateTimeImmutable::createFromFormat('Y-m-d', $request->startDate);
    $endDate = DateTimeImmutable::createFromFormat('Y-m-d', $request->endDate);

    if (!$startDate || !$endDate) {
      throw new Exception("Format de date invalide (attendu: YYYY-MM-DD).");
    }

    // Normalisation (Début à 00:00, Fin à 23:59:59)
    $startDate = $startDate->setTime(0, 0, 0);
    $endDate = $endDate->setTime(23, 59, 59);

    // Validation 1 : Pas dans le passé
    $today = new DateTimeImmutable('today');
    if ($startDate < $today) {
      throw new Exception("La date de début ne peut pas être dans le passé.");
    }

    // Validation 2 : Date de fin > Date de début
    if ($endDate <= $startDate) {
      throw new Exception("La date de fin doit être après la date de début.");
    }

    // Validation 3 : Durée minimum de 1 mois
    // On calcule la date minimale acceptée (Date début + 1 mois)
    $minEndDate = $startDate->modify('+1 month')->modify('-1 day')->setTime(23, 59, 59);

    if ($endDate < $minEndDate) {
      throw new Exception("La durée de l'abonnement doit être d'au moins 1 mois.");
    }

    // 4. Vérification de Capacité
    $activeSubs = $this->subscriptionRepository->countActiveForParking(
      $parking->getId(),
      $startDate,
      $endDate
    );

    if ($activeSubs >= $parking->getTotalPlaces()) {
      throw new Exception("Impossible de souscrire : Le quota d'abonnements est atteint sur cette période.");
    }

    // 5. Calcul du PRIX TOTAL (Optionnel mais recommandé)

    // 6. Création de l'entité
    $subscription = new UserSubscription(
      $this->idGenerator->generate(),
      $request->userId,
      $parking->getId(),
      $selectedPlan->getId(),
      $selectedPlan->getName(),
      $selectedPlan->getMonthlyPrice(),
      $startDate,
      $endDate,
      $selectedPlan->getSchedule()
    );

    // 7. Sauvegarde
    $this->subscriptionRepository->save($subscription);

    return new SubscribeToParkingPlanResponse($subscription);
  }
}
