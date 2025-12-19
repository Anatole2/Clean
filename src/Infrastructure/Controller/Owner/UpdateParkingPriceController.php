<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Owner;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\Owner\UpdateParkingPrice\UpdateParkingPrice;
use App\UseCase\Owner\UpdateParkingPrice\UpdateParkingPriceRequest;
use Twig\Environment;

class UpdateParkingPriceController extends AbstractController
{
  public function __construct(
    private UpdateParkingPrice $useCase,
    private PresenterFactory $presenterFactory,
    private Environment $twig,
  ) {}

  public function __invoke(string $id): void
  {
    try {
      $this->ensureIsOwner();
      $input = $this->getRequestData();

      // Nettoyage sommaire des données
      $rawPrices = $input['priceGridConfig'] ?? $input['prices'] ?? [];
      $cleanPrices = [];
      foreach ($rawPrices as $duration => $price) {
        if ((int)$price >= 0) {
          $cleanPrices[(string)$duration] = (int)$price;
        }
      }
      if (empty($cleanPrices)) {
        throw new \Exception("La grille tarifaire reçue est vide ou invalide (Clés trouvées dans l'input : " . implode(', ', array_keys($input)) . ")");
      }
      // Exécution Use Case
      $request = new UpdateParkingPriceRequest($id, $this->getAuthUserId(), $cleanPrices);
      $response = $this->useCase->execute($request);

      // Présentation
      $presenter = $this->presenterFactory->create('Owner\\UpdateParkingPrice');

      if ($this->wantsJson()) {
        header('Content-Type: application/json');
      }

      echo $presenter->present($response);
    } catch (\Exception $e) {
      echo $this->twig->render('error.html.twig', ['message' => $e->getMessage()]);
    }
  }
}
