<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Owner;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Infrastructure\Controller\AbstractController;
use Twig\Environment;

class ShowUpdateParkingHoursFormController extends AbstractController
{
  public function __construct(
    private ParkingRepositoryInterface $repository,
    private Environment $twig
  ) {}

  public function __invoke(string $parkingId): void
  {
    $this->ensureIsOwner();

    $parking = $this->repository->findById($parkingId);

    if (!$parking || $parking->getOwnerId() !== $this->getAuthUserId()) {
      // Redirection ou erreur si pas proprio
      header('Location: /parkings');
      exit;
    }

    echo $this->twig->render('owner/update_parking_hours_form.html.twig', [
      'parking' => $parking
    ]);
  }
}
