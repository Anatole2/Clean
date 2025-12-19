<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\User\GenerateInvoice\GenerateInvoice;
use App\UseCase\User\GenerateInvoice\GenerateInvoiceRequest;

class GenerateInvoiceController extends AbstractController
{
  public function __construct(
    private GenerateInvoice $useCase,
    private PresenterFactory $presenterFactory
  ) {}

  public function __invoke(string $id): void
  {
    try {
      $this->ensureIsUser();

      $request = new GenerateInvoiceRequest(
        $id,
        $this->getAuthUserId()
      );
      // Exécution
      $response = $this->useCase->execute($request);

      // Présentation
      $presenter = $this->presenterFactory->create('User\\GenerateInvoice');

      if ($this->wantsJson()) {
        header('Content-Type: application/json');
      }

      echo $presenter->present($response->invoice);
    } catch (\Exception $e) {
      $this->sendError($e->getMessage(), 404);
    }
  }
}
