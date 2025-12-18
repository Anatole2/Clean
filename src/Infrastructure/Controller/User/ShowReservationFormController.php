<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Infrastructure\Controller\AbstractController;
use App\Domain\Repository\ParkingRepositoryInterface;
use Twig\Environment;

class ShowReservationFormController extends AbstractController
{
  public function __construct(
    private ParkingRepositoryInterface $parkingRepo,
    private Environment $twig
  ) {}

  public function __invoke(): void
  {
    try {
      $this->ensureIsUser();

      $parkingId = $_GET['parkingId'] ?? null;
      if (!$parkingId) {
        throw new \Exception("ID du parking manquant.");
      }

      $parking = $this->parkingRepo->findById($parkingId);
      if (!$parking) {
        throw new \Exception("Parking introuvable.");
      }

      echo $this->twig->render('user/new_reservation_form.html.twig', [
        'parking' => $parking
      ]);
    } catch (\Exception $e) {
      echo $this->twig->render('error.html.twig', ['message' => $e->getMessage()]);
    }
  }
}
