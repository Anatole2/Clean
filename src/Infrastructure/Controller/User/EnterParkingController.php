<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Infrastructure\Controller\AbstractController;
use App\UseCase\User\EnterParking\EnterParking;
use App\UseCase\User\EnterParking\EnterParkingRequest;

class EnterParkingController extends AbstractController
{
  public function __construct(private EnterParking $useCase) {}

  public function __invoke(string $id): void
  {
    try {
      $this->ensureIsUser();
      $user = $this->getAuthUser();

      // L'ID du parking vient de l'URL (/parkings/{id}/enter)
      $request = new EnterParkingRequest($user->id, $id);

      $response = $this->useCase->execute($request);

      // On renvoie un JSON simple (pas besoin de Presenter complexe pour ça)
      header('Content-Type: application/json');
      echo json_encode([
        'status' => 'success',
        'message' => 'Bienvenue ! Barrière ouverte.',
        'session_id' => $response->session->getId(),
        'entry_time' => $response->session->getEntryTime()->format('Y-m-d H:i:s')
      ]);
    } catch (\Exception $e) {
      $this->sendError($e->getMessage(), 400);
    }
  }
}
