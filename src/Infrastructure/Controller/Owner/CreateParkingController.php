<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Owner;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\Owner\CreateParking\CreateParking;
use App\UseCase\Owner\CreateParking\CreateParkingRequest;

class CreateParkingController extends AbstractController
{
  public function __construct(
    private CreateParking $useCase,
    private PresenterFactory $presenterFactory
  ) {}

  public function __invoke(): void
  {
    // 1. Sécurité
    $this->ensureIsOwner();

    // 2. Récupération des données (VERSION ROBUSTE)
    // On récupère les données JSON potentielles
    $jsonData = $this->getRequestData();


    // --- NETTOYAGE / CASTING ---

    // A. Nettoyage Prix
    $cleanPrices = [];
    if (isset($input['priceGridConfig']) && is_array($input['priceGridConfig'])) {
      foreach ($input['priceGridConfig'] as $duration => $price) {
        // On s'assure qu'on a bien des entiers
        $val = (int)$price;
        if ($val > 0) { // On ignore les prix à 0 ou nuls
          $cleanPrices[(int)$duration] = $val;
        }
      }
    }

    // B. Nettoyage Horaires
    $cleanHours = [];
    if (isset($input['openingHoursConfig']) && is_array($input['openingHoursConfig'])) {
      foreach ($input['openingHoursConfig'] as $slot) {
        $cleanHours[] = [
          'startDay'  => (int) ($slot['startDay'] ?? 1),
          'startTime' => (string) ($slot['startTime'] ?? '08:00'),
          'endDay'    => (int) ($slot['endDay'] ?? 1),
          'endTime'   => (string) ($slot['endTime'] ?? '18:00'),
        ];
      }
    }

    // 3. Création de la Request (ORDRE VALIDÉ)
    $request = new CreateParkingRequest(
      $this->getAuthUserId(),
      (string) ($input['name'] ?? ''),
      (float) ($input['latitude'] ?? 0.0),
      (float) ($input['longitude'] ?? 0.0),
      (int) ($input['totalPlaces'] ?? 0),
      $cleanPrices,
      $cleanHours
    );

    // 4. Exécution
    $responseDTO = $this->useCase->execute($request);

    // 5. Présentation
    $presenter = $this->presenterFactory->create('Owner\\CreateParking');

    http_response_code(201);
    echo $presenter->present($responseDTO);
  }
}
