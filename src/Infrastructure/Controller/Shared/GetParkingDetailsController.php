<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Shared;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\Shared\GetParkingDetails\GetParkingDetails;
use App\UseCase\Shared\GetParkingDetails\GetParkingDetailsRequest;
use Twig\Environment;

class GetParkingDetailsController extends AbstractController
{
  public function __construct(
    private GetParkingDetails $useCase,
    private PresenterFactory $presenterFactory,
    private Environment $twig
  ) {}

  public function __invoke(string $id): void
  {
    try {
      // Optionnel : ensureIsUser() ou ensureIsOwner() si tu veux restreindre
      // Mais généralement voir une fiche parking est public ou accessible aux deux.
      $this->getAuthUserId();
      $this->ensureIsUser();

      $request = new GetParkingDetailsRequest(
        $id
      );
      $response = $this->useCase->execute($request);

      // On utilise 'Shared\GetParkingDetails' pour la factory
      $presenter = $this->presenterFactory->create('Shared\\GetParkingDetails');

      if ($this->wantsJson()) {
        header('Content-Type: application/json');
      }

      echo $presenter->present($response);
    } catch (\Exception $e) {
      $this->handleError($e);
    }
  }
  private function handleError(\Exception $e): void
  {
    if ($this->wantsJson()) {
      $this->sendError($e->getMessage(), 400);
    } else {
      echo $this->twig->render('error.html.twig', [
        'code' => $e->getCode(),
        'message' => $e->getMessage()
      ]);
    }
  }
}
