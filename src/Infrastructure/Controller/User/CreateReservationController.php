<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\User\CreateReservation\CreateReservation;
use App\UseCase\User\CreateReservation\CreateReservationRequest;
use DateTimeImmutable;

class CreateReservationController extends AbstractController
{
  public function __construct(
    private CreateReservation $useCase,
    private PresenterFactory $presenterFactory
  ) {}

  public function __invoke(): void
  {
    try {
      $this->ensureIsUser();
      $user = $this->getAuthUser();

      // 1. Récupération des données POST
      $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

      $parkingId = $data['parkingId'] ?? null;
      $startStr = $data['start_time'] ?? null;
      $endStr = $data['end_time'] ?? null;

      if (!$parkingId || !$startStr || !$endStr) {
        throw new \InvalidArgumentException("Tous les champs sont requis.");
      }

      // 2. Conversion des dates (HTML envoie "YYYY-MM-DDTHH:MM")
      $start = new DateTimeImmutable($startStr);
      $end = new DateTimeImmutable($endStr);

      // 3. Exécution Use Case
      $request = new CreateReservationRequest(
        $user->id,
        $parkingId,
        $start,
        $end
      );

      $response = $this->useCase->execute($request);

      // 4. Présentation
      $presenter = $this->presenterFactory->create('User\\CreateReservation');
      echo $presenter->present($response);
    } catch (\Exception $e) {
      $this->handleError($e);
    }
  }

  private function handleError(\Exception $e): void
  {
    http_response_code($e instanceof \InvalidArgumentException ? 400 : 500);

    if ($this->wantsJson()) {
      echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    } else {
      // En HTML, on redirige vers le formulaire avec l'erreur (simple version)
      // L'idéal serait de réafficher le formulaire avec les valeurs précédentes
      echo "Erreur : " . $e->getMessage() . " <a href='javascript:history.back()'>Retour</a>";
    }
  }
}
