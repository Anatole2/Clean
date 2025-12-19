<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\User\SubscribeToParkingPlan\SubscribeToParkingPlan;
use App\UseCase\User\SubscribeToParkingPlan\SubscribeToParkingPlanRequest;
use Twig\Environment;

class SubscribeToParkingPlanController extends AbstractController
{
  public function __construct(
    private SubscribeToParkingPlan $useCase,
    private PresenterFactory $presenterFactory,
    private Environment $twig,
  ) {}

  public function __invoke(string $parkingId): void
  {
    try {
      // 1. Sécurité : On vérifie que c'est bien un USER connecté
      // (Si pas connecté, AbstractController redirige ou renvoie 401)
      $this->ensureIsUser();

      // 2. Récupération des données (JSON ou $_POST unifiés)
      // Plus besoin de faire json_decode manuel ici
      $data = $this->getRequestData();

      if (empty($data['plan_id']) || empty($data['start_date']) || empty($data['end_date'])) {
        throw new \Exception("Les champs 'plan_id', 'start_date' et 'end_date' sont obligatoires.");
      }

      $request = new SubscribeToParkingPlanRequest(
        $this->getAuthUserId(),
        $parkingId,
        $data['plan_id'],
        $data['start_date'],
        $data['end_date']
      );

      // 5. Exécution
      $response = $this->useCase->execute($request);

      // 6. Présentation
      // La Factory choisira JsonPresenter ou HtmlPresenter selon le header Accept
      $presenter = $this->presenterFactory->create('User\\SubscribeToParkingPlan');
      echo $presenter->present($response);
    } catch (\Exception $e) {
      // 7. Gestion d'erreur unifiée

      // Si c'est une API (cURL / Fetch JSON), on renvoie du JSON propre
      if ($this->wantsJson()) {
        $this->sendError($e->getMessage(), 400);
        return;
      }

      // Si c'est un navigateur (HTML), on affiche l'erreur simplement (ou page d'erreur dédiée)
      http_response_code(400);

      echo $this->twig->render('error.html.twig', ['message' => $e->getMessage()]);
    }
  }
}
