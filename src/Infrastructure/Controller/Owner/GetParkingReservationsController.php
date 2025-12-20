<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Owner;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\Owner\GetParkingReservations\GetParkingReservationsRequest;
use App\UseCase\Owner\GetParkingReservations\GetParkingReservations;

class GetParkingReservationsController extends AbstractController
{
  public function __construct(
    private GetParkingReservations $useCase,
    private PresenterFactory $presenterFactory
  ) {}

  public function __invoke(string $id): void
  {
    $this->ensureIsOwner();

    $request = new GetParkingReservationsRequest($id, $this->getAuthUserId());
    $response = $this->useCase->execute($request);

    $presenter = $this->presenterFactory->create('Owner\\GetParkingReservations');

    if ($this->wantsJson()) {
      header('Content-Type: application/json');
    }

    echo $presenter->present($response);
  }
}
