<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Owner;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\Owner\UpdateParkingHours\UpdateParkingHours;
use App\UseCase\Owner\UpdateParkingHours\UpdateParkingHoursRequest;
use Twig\Environment;

class UpdateParkingHoursController extends AbstractController
{
  public function __construct(
    private UpdateParkingHours $useCase,
    private PresenterFactory $presenterFactory,
    private Environment $twig
  ) {}

  public function __invoke(string $parkingId): void
  {
    try {
      $this->ensureIsOwner();
      $input = $this->getRequestData();

      // 1. Récupération des données brutes
      // Le formulaire JS envoie openingHoursConfig[...]
      $rawHours = $input['openingHoursConfig'] ?? [];

      // 2. Nettoyage et Casting
      $cleanHours = [];
      if (is_array($rawHours)) {
        foreach ($rawHours as $slot) {
          // On s'assure d'avoir la structure attendue par WeeklySchedule
          if (isset($slot['startDay'], $slot['startTime'], $slot['endTime'])) {
            $cleanHours[] = [
              'startDay'  => (int) $slot['startDay'],
              'startTime' => (string) $slot['startTime'],
              'endDay'    => (int) ($slot['endDay'] ?? $slot['startDay']), // Fallback même jour si manquant
              'endTime'   => (string) $slot['endTime'],
            ];
          }
        }
      }

      // Validation basique
      if (empty($cleanHours)) {
        throw new \Exception("Veuillez définir au moins une plage horaire.");
      }

      // 3. Exécution
      $request = new UpdateParkingHoursRequest(
        $parkingId,
        $this->getAuthUserId(),
        $cleanHours
      );

      $response = $this->useCase->execute($request);

      // 4. Présentation
      $presenter = $this->presenterFactory->create('Owner\\UpdateParkingHours'); // Assure-toi d'avoir configuré la factory

      if ($this->wantsJson()) {
        header('Content-Type: application/json');
      }

      echo $presenter->present($response);
    } catch (\Exception $e) {
      if ($this->wantsJson()) {
        $this->sendError($e->getMessage(), 400);
      } else {

        echo $this->twig->render('error.html.twig', ['message' => $e->getMessage()]);
      }
    }
  }
}
