<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Infrastructure\Controller\AbstractController;
use App\UseCase\User\ExitParking\ExitParking;
use App\UseCase\User\ExitParking\ExitParkingRequest;

class ExitParkingController extends AbstractController
{
  public function __construct(private ExitParking $useCase) {}

  public function __invoke(string $id): void
  {
    try {
      $this->ensureIsUser();
      $user = $this->getAuthUser();

      $request = new ExitParkingRequest($user->id, $id);
      $response = $this->useCase->execute($request);

      header('Content-Type: application/json');
      echo json_encode([
        'status' => 'success',
        'message' => 'Sortie confirmée. À bientôt !',
        'exit_time' => $response->session->getExitTime()->format('Y-m-d H:i:s'),
        'bill' => [
          'overstay_minutes' => $response->overstayMinutes,
          'penalty_applied' => $response->penaltyApplied,
          'total_extra_due' => $response->extraCost / 100 . ' €'
        ]
      ]);
    } catch (\Exception $e) {
      $this->sendError($e->getMessage(), 400);
    }
  }
}
