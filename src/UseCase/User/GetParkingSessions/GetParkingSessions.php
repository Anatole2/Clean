<?php

declare(strict_types=1);

namespace App\UseCase\User\GetParkingSessions;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\UseCase\User\GetParkingSessions\GetParkingSessionsRequest;
use App\UseCase\User\GetParkingSessions\GetParkingSessionsResponse;
use App\UseCase\User\GetParkingSessions\ParkingSessionDto;

class GetParkingSessions
{
  public function __construct(
    private ParkingSessionRepositoryInterface $sessionRepository,
    private ParkingRepositoryInterface $parkingRepository
  ) {}

  public function execute(GetParkingSessionsRequest $request): GetParkingSessionsResponse
  {
    // 1. Récupérer l'historique brut
    $sessions = $this->sessionRepository->findByUserId($request->userId);

    $dtos = [];
    $now = new \DateTimeImmutable();

    foreach ($sessions as $session) {
      // 2. Récupérer le nom du parking pour affichage
      $parking = $this->parkingRepository->findById($session->getParkingId());
      $parkingName = $parking ? $parking->getName() : 'Parking inconnu';

      // 3. Logique Active vs Terminée
      $isActive = ($session->getExitTime() === null);

      $exitTimeString = null;
      $status = 'TERMINÉ';

      // Calcul de la durée
      if ($isActive) {
        // Si actif : Durée = Entre Entrée et Maintenant
        $diff = $session->getEntryTime()->diff($now);
        $durationString = $diff->format('%h h %i min') . ' (en cours)';
        $status = 'EN COURS';
      } else {
        // Si terminé : Durée = Entre Entrée et Sortie réelle
        $diff = $session->getEntryTime()->diff($session->getExitTime());
        $durationString = $diff->format('%h h %i min');
        $exitTimeString = $session->getExitTime()->format('d/m/Y H:i');
      }

      // 4. Création du DTO
      $dtos[] = new ParkingSessionDto(
        $session->getId(),
        $parkingName,
        $session->getEntryTime()->format('d/m/Y H:i'),
        $exitTimeString,
        $durationString,
        $session->getPricePaid() / 100, // Conversion centimes -> Euros
        $status,
        $isActive
      );
    }

    return new GetParkingSessionsResponse($dtos);
  }
}
