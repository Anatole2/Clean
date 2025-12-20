<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Owner;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\Owner\GetParkingAvailability\GetParkingAvailabilityRequest;
use App\UseCase\Owner\GetParkingAvailability\GetParkingAvailability;
use DateTimeImmutable;
use Exception;

class GetParkingAvailabilityController extends AbstractController
{
  public function __construct(
    private GetParkingAvailability $useCase,
    private PresenterFactory $presenterFactory
  ) {}

  public function __invoke(string $id): void
  {
    $this->ensureIsOwner();

    // Récupération de la date passée en paramètre GET (ex: ?date=2025-12-25T20:00)
    // Sinon, utilise "maintenant".
    $dateParam = $_GET['date'] ?? 'now';

    try {
      $checkTime = new DateTimeImmutable($dateParam);
    } catch (Exception $e) {
      // Fallback si la date est mal formatée
      $checkTime = new DateTimeImmutable();
    }

    $request = new GetParkingAvailabilityRequest($id, $this->getAuthUserId(), $checkTime);
    $response = $this->useCase->execute($request);

    // Présentation
    $presenter = $this->presenterFactory->create('Owner\\GetParkingAvailability');

    if ($this->wantsJson()) {
      header('Content-Type: application/json');
    }

    echo $presenter->present($response);
  }
}
