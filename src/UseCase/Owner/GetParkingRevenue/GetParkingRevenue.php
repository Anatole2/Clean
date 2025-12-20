<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetParkingRevenue;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\UseCase\Owner\GetParkingRevenue\GetParkingRevenueRequest;
use App\UseCase\Owner\GetParkingRevenue\GetParkingRevenueResponse;
use DateTimeImmutable;
use Exception;

class GetParkingRevenue
{
  public function __construct(
    private ParkingRepositoryInterface $parkingRepo,
    private ReservationRepositoryInterface $resRepo,
    private UserSubscriptionRepositoryInterface $subRepo
  ) {}

  public function execute(GetParkingRevenueRequest $request): GetParkingRevenueResponse
  {
    // 1. Vérifications
    $parking = $this->parkingRepo->findById($request->parkingId);
    if (!$parking) throw new Exception("Parking introuvable.");
    if ($parking->getOwnerId() !== $request->ownerId) throw new Exception("Accès refusé.");

    // 2. Définition de la période (Du 1er au dernier jour du mois)
    // Création du 1er du mois à 00:00:00
    $start = (new DateTimeImmutable())->setDate($request->year, $request->month, 1)->setTime(0, 0, 0);
    // Création du dernier jour à 23:59:59
    $end = $start->modify('last day of this month')->setTime(23, 59, 59);

    // 3. Calculs via les Repositories
    $revRes = $this->resRepo->calculateRevenue($request->parkingId, $start, $end);
    $revSub = $this->subRepo->calculateRevenue($request->parkingId, $start, $end);

    return new GetParkingRevenueResponse(
      $parking,
      $request->month,
      $request->year,
      $revRes,
      $revSub,
      $revRes + $revSub
    );
  }
}
