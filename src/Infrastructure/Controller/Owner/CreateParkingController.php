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
    try {
      // 1. Sécurité
      $this->ensureIsOwner();

      // 2. Mapping Requête
      $input = $this->getRequestData();
      $request = new CreateParkingRequest(
        $this->getAuthUserId(), // ID sécurisé du token
        $input['name'] ?? '',
        (float)($input['latitude'] ?? 0),
        (float)($input['longitude'] ?? 0),
        (int)($input['totalPlaces'] ?? 0),
        $input['priceGridConfig'] ?? [],
        $input['openingHoursConfig'] ?? []
      );

      // 3. Exécution Use Case
      $responseDTO = $this->useCase->execute($request);

      // 4. Présentation (Adapter)
      $presenter = $this->presenterFactory->create('CreateParking');

      http_response_code(201);
      echo $presenter->present($responseDTO);
    } catch (\Exception $e) {
      $code = $e->getCode();
      if (!is_int($code) || $code < 400 || $code > 599) {
        $code = 500; // Erreur serveur par défaut
      }
      $this->sendError($e->getMessage(), $code);
    }
  }
}
