<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Owner;

use App\Domain\Repository\ParkingRepositoryInterface;
use App\Infrastructure\Controller\AbstractController;
use Twig\Environment;

class ShowUpdateParkingPriceFormController extends AbstractController
{
  public function __construct(
    private ParkingRepositoryInterface $repository,
    private Environment $twig
  ) {}

  public function __invoke(string $id): void
  {
    // 1. Sécurité
    $this->ensureIsOwner();

    // 2. Récupération
    $parking = $this->repository->findById($id);

    if (!$parking || $parking->getOwnerId() !== $this->getAuthUserId()) {
      // Redirection ou erreur simple si accès interdit
      header('Location: /parkings');
      exit;
    }

    // 3. Affichage du template "Formulaire"
    echo $this->twig->render('owner/update_parking_prices_form.html.twig', [
      'parking' => $parking
    ]);
  }
}
