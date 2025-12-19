<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\User\GetParkingSessions\GetParkingSessions;
use App\UseCase\User\GetParkingSessions\GetParkingSessionsRequest;
use twig\Environment;

class GetParkingSessionsController extends AbstractController
{
  public function __construct(
    private GetParkingSessions $useCase,
    private PresenterFactory $presenterFactory,
    private Environment $twig
  ) {}

  public function __invoke(): void
  {
    try {
      $this->ensureIsUser();

      $request = new GetParkingSessionsRequest($this->getAuthUserId());
      $response = $this->useCase->execute($request);

      $presenter = $this->presenterFactory->create('User\\GetParkingSessions');

      if ($this->wantsJson()) {
        header('Content-Type: application/json');
      }

      // On passe la liste des sessions au presenter
      echo $presenter->present($response);
    } catch (\Exception $e) {
      if ($this->wantsJson()) {
        $this->sendError($e->getMessage(), 500);
      } else {
        echo $this->twig->render('error.html.twig', ['message' => $e->getMessage()]);
      }
    }
  }
}
