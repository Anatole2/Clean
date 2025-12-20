<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Owner;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\Owner\GetParkingRevenue\GetParkingRevenueRequest;
use App\UseCase\Owner\GetParkingRevenue\GetParkingRevenue;

class GetParkingRevenueController extends AbstractController
{
  public function __construct(
    private GetParkingRevenue $useCase,
    private PresenterFactory $presenterFactory
  ) {}

  public function __invoke(string $id): void
  {
    $this->ensureIsOwner();

    // Récupère mois/année depuis l'URL (?month=12&year=2025), sinon date actuelle
    $month = (int) ($_GET['month'] ?? date('m'));
    $year  = (int) ($_GET['year'] ?? date('Y'));

    $request = new GetParkingRevenueRequest($id, $this->getAuthUserId(), $month, $year);
    $response = $this->useCase->execute($request);

    $presenter = $this->presenterFactory->create('Owner\\GetParkingRevenue');

    if ($this->wantsJson()) {
      header('Content-Type: application/json');
    }

    echo $presenter->present($response);
  }
}
