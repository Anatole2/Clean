<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\User\GetReservations\GetReservations;
use App\UseCase\User\GetReservations\GetReservationsRequest;
use Twig\Environment;

class GetReservationsController extends AbstractController
{
  public function __construct(
    private GetReservations $useCase,
    private PresenterFactory $presenterFactory,
    private Environment $twig
  ) {}

  public function __invoke(): void
  {
    try {
      $this->ensureIsUser();

      // 1. Création de la Request DTO
      $request = new GetReservationsRequest($this->getAuthUserId());

      // 2. Exécution
      $response = $this->useCase->execute($request);

      // 3. Présentation via la Factory
      $presenter = $this->presenterFactory->create('User\\GetReservations');

      if ($this->wantsJson()) {
        header('Content-Type: application/json');
      }

      // 4. Affichage du résultat
      echo $presenter->present($response);
    } catch (\Exception $e) {
      if ($this->wantsJson()) {
        $this->sendError($e->getMessage(), 401);
      } else {
        echo $this->twig->render('error.html.twig', ['message' => $e->getMessage()]);
      }
    }
  }
}
