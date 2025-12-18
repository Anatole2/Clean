<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Owner;

use App\Infrastructure\Controller\AbstractController;
use Twig\Environment;
use App\Domain\Repository\ParkingRepositoryInterface;

class ShowAddParkingSubscriptionPlanController extends AbstractController
{
  public function __construct(
    private Environment $twig,
    private ParkingRepositoryInterface $parkingRepository
  ) {}

  public function __invoke(string $id): void
  {
    try {
      $this->ensureIsOwner();
      $owner = $this->getAuthUser();

      // 1. Récupération du parking
      $parking = $this->parkingRepository->findById($id);

      // 2. Vérifications de sécurité
      if (!$parking) {
        throw new \Exception("Ce parking n'existe pas.");
      }

      if ($parking->getOwnerId() !== $owner->id) {
        throw new \Exception("Ce parking ne vous appartient pas.");
      }

      // 3. Envoi à la vue avec le NOM
      echo $this->twig->render('owner/add_parking_subscription_plan_form.html.twig', [
        'parkingId' => $id,
        'parkingName' => $parking->getName()
      ]);
    } catch (\Exception $e) {
      // En cas d'erreur, on affiche la page d'erreur
      echo $this->twig->render('error.html.twig', ['message' => $e->getMessage()]);
    }
  }
}
