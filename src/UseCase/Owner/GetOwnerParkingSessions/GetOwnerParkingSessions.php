<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetOwnerParkingSessions;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\UseCase\Owner\GetOwnerParkingSessions\GetOwnerParkingSessionsRequest;
use App\UseCase\Owner\GetOwnerParkingSessions\GetOwnerParkingSessionsResponse;
use Exception;

class GetOwnerParkingSessions
{
  public function __construct(
    private ParkingSessionRepositoryInterface $sessionRepository,
    private ParkingRepositoryInterface $parkingRepository
  ) {}

  public function execute(GetOwnerParkingSessionsRequest $request): GetOwnerParkingSessionsResponse
  {
    // 1. Vérif Parking
    $parking = $this->parkingRepository->findById($request->parkingId);
    if (!$parking) throw new Exception("Parking introuvable.");

    // 2. Vérif Owner
    if ($parking->getOwnerId() !== $request->ownerId) {
      throw new Exception("Accès refusé.");
    }

    // 3. Récupération des SESSIONS
    $sessions = $this->sessionRepository->findByParkingId($request->parkingId);

    return new GetOwnerParkingSessionsResponse($parking, $sessions);
  }
}
