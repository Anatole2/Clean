<?php

declare(strict_types=1);

namespace App\UseCase\Shared\GetParkingDetails;

use App\Domain\Repository\ParkingRepositoryInterface;
use Exception;

class GetParkingDetails
{
  public function __construct(
    private ParkingRepositoryInterface $repository
  ) {}

  public function execute(GetParkingDetailsRequest $request): GetParkingDetailsResponse
  {
    $parking = $this->repository->findById($request->parkingId);

    if (!$parking) {
      throw new Exception("Parking introuvable.");
    }

    return new GetParkingDetailsResponse($parking);
  }
}
