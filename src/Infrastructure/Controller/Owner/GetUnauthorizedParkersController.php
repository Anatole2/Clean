<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Owner;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\Owner\GetUnauthorizedParkers\GetUnauthorizedParkersRequest;
use App\UseCase\Owner\GetUnauthorizedParkers\GetUnauthorizedParkers;

class GetUnauthorizedParkersController extends AbstractController
{
  public function __construct(
    private GetUnauthorizedParkers $useCase,
    private PresenterFactory $presenterFactory
  ) {}

  public function __invoke(string $id): void
  {
    $this->ensureIsOwner();

    $request = new GetUnauthorizedParkersRequest($id, $this->getAuthUserId());
    $response = $this->useCase->execute($request);

    $presenter = $this->presenterFactory->create('Owner\\GetUnauthorizedParkers');

    if ($this->wantsJson()) {
      header('Content-Type: application/json');
    }

    echo $presenter->present($response);
  }
}
