<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Owner;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\Owner\GetOwnerParkings\GetOwnerParkings;
use App\UseCase\Owner\GetOwnerParkings\GetOwnerParkingsRequest;

class ListOwnerParkingsController extends AbstractController
{
  public function __construct(
    private GetOwnerParkings $useCase,
    private PresenterFactory $presenterFactory
  ) {}

  public function __invoke(): void
  {
    // 1. Sécurité
    $this->ensureIsOwner();

    // 2. Prépare la requête avec l'ID du owner connecté
    $ownerId = $this->getAuthUserId();
    $request = new GetOwnerParkingsRequest($ownerId);

    // 3. Exécute le Use Case
    $response = $this->useCase->execute($request);

    // 4. Présentation
    // La Factory va instancier Html... ou Json... selon le header Accept
    $presenter = $this->presenterFactory->create('Owner\\ListOwnerParkings');

    echo $presenter->present($response);
  }
}
