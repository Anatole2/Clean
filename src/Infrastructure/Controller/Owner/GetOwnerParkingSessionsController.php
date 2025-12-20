<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Owner;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\Owner\GetOwnerParkingSessions\GetOwnerParkingSessionsRequest;
use App\UseCase\Owner\GetOwnerParkingSessions\GetOwnerParkingSessions;

class GetOwnerParkingSessionsController extends AbstractController
{
  public function __construct(
    private GetOwnerParkingSessions $useCase,
    private PresenterFactory $presenterFactory
  ) {}

  public function __invoke(string $id): void
  {
    $this->ensureIsOwner();

    $request = new GetOwnerParkingSessionsRequest($id, $this->getAuthUserId());
    $response = $this->useCase->execute($request);

    $presenter = $this->presenterFactory->create('Owner\\GetOwnerParkingSessions');

    if ($this->wantsJson()) {
      header('Content-Type: application/json');
    }

    echo $presenter->present($response);
  }
}
