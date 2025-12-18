<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Owner;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\Owner\AddParkingSubscriptionPlan\AddParkingSubscriptionPlan;
use App\UseCase\Owner\AddParkingSubscriptionPlan\AddParkingSubscriptionPlanRequest;

class AddParkingSubscriptionPlanController extends AbstractController
{
  public function __construct(
    private AddParkingSubscriptionPlan $useCase,
    private PresenterFactory $presenterFactory
  ) {}

  public function __invoke(string $id): void
  {
    try {
      // 1. Sécurité
      $this->ensureIsOwner();
      $owner = $this->getAuthUser();

      // 2. Extraction & Nettoyage des données
      $rawData = $this->getRequestData();
      $formattedData = $this->formatData($rawData);

      // 3. Request
      $request = new AddParkingSubscriptionPlanRequest(
        ownerId: $owner->id,
        parkingId: $id,
        planName: $formattedData['name'],
        monthlyPrice: $formattedData['price'],
        ruleConfig: $formattedData['schedule']
      );

      // 4. Use Case
      $response = $this->useCase->execute($request);

      // 5. Présentation via Factory
      // La Factory va choisir le bon presenter (JSON ou HTML) selon le contexte
      $presenter = $this->presenterFactory->create('Owner\\AddParkingSubscriptionPlan');

      // On définit le code 201 Created par défaut (le HtmlPresenter pourra le surcharger via header si besoin de rediriger)
      http_response_code(201);

      // Le presenter retourne une string (JSON ou rien si redirection)
      echo $presenter->present($response);
    } catch (\Exception $e) {
      $this->handleError($e);
    }
  }

  private function formatData(array $input): array
  {
    $name = $input['name'] ?? null;
    $price = isset($input['price']) ? (int)((float)$input['price'] * 100) : null;

    // Gestion Schedule (JSON ou HTML plat)
    if (isset($input['schedule']) && is_array($input['schedule'])) {
      $schedule = $input['schedule'];
    } else {
      $schedule = [
        [
          'startDay' => (int)($input['start_day'] ?? 1),
          'startTime' => $input['start_time'] ?? '00:00',
          'endDay' => (int)($input['end_day'] ?? 7),
          'endTime' => $input['end_time'] ?? '23:59',
        ]
      ];
    }

    if (!$name || $price === null) {
      throw new \Exception("Champs manquants : name, price");
    }

    return [
      'name' => $name,
      'price' => $price,
      'schedule' => $schedule
    ];
  }

  private function handleError(\Exception $e): void
  {
    // On garde une gestion d'erreur basique ici car le Presenter d'erreur n'est pas instancié
    if ($this->wantsJson()) {
      $this->sendError($e->getMessage(), 400);
    } else {
      http_response_code(400);
      echo "<h1>Erreur</h1><p>" . htmlspecialchars($e->getMessage()) . "</p><a href='javascript:history.back()'>Retour</a>";
    }
  }
}
