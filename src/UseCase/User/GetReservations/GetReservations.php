<?php

declare(strict_types=1);

namespace App\UseCase\User\GetReservations;

use App\Domain\Repository\ReservationRepositoryInterface;
use App\UseCase\User\GetReservations\GetReservationsRequest;
use App\UseCase\User\GetReservations\GetReservationsResponse;
use App\UseCase\User\GetReservations\ReservationSummary;
use App\Domain\Entity\Reservation; // Pour accéder aux constantes

class GetReservations
{
  public function __construct(
    private ReservationRepositoryInterface $repository
  ) {}

  public function execute(GetReservationsRequest $request): GetReservationsResponse
  {
    // 1. Récupération des entités
    $reservationsEntities = $this->repository->findByUserId($request->userId);

    // 2. On définit l'instant présent pour comparaison
    $now = new \DateTimeImmutable();

    // 3. Mapping Entité -> DTO
    $summaries = array_map(function ($entity) use ($now) {

      $isConfirmed = $entity->getStatus() === Reservation::STATUS_CONFIRMED;
      $isPast = $now > $entity->getEndTime();

      return new ReservationSummary(
        $entity->getId(),
        $entity->getParkingId(),
        $entity->getStartTime()->format('Y-m-d H:i'),
        $entity->getEndTime()->format('Y-m-d H:i'),
        $entity->getPricePaidInCents(),
        $entity->getStatus(),

        ($isConfirmed && $isPast)
      );
    }, $reservationsEntities);

    return new GetReservationsResponse($summaries);
  }
}
