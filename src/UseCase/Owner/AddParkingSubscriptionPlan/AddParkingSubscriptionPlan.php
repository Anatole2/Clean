<?php

declare(strict_types=1);

namespace App\UseCase\Owner\AddParkingSubscriptionPlan;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\SubscriptionPlan;
use App\Domain\ValueObject\WeeklySchedule;
use Exception;

class AddParkingSubscriptionPlan
{
  public function __construct(
    private ParkingRepositoryInterface $repository
  ) {}

  public function execute(AddParkingSubscriptionPlanRequest $request): AddParkingSubscriptionPlanResponse
  {
    // 1. Récupération du parking
    $parking = $this->repository->findById($request->parkingId);

    if (!$parking) {
      throw new Exception("Parking introuvable.");
    }

    // 2. Sécurité : Vérification du propriétaire
    if ($parking->getOwnerId() !== $request->ownerId) {
      throw new Exception("Accès refusé : ce parking ne vous appartient pas.");
    }

    // 3. Création des Value Objects (Validation incluse)
    $rule = new WeeklySchedule($request->ruleConfig);

    $plan = new SubscriptionPlan(
      $request->planName,
      $request->monthlyPrice,
      $rule
    );

    // 4. Ajout du plan à l'entité
    $parking->addSubscriptionPlan($plan);

    // 5. Sauvegarde
    $this->repository->save($parking);

    // 6. Retour de la réponse
    return AddParkingSubscriptionPlanResponse::fromParking($parking);
  }
}
