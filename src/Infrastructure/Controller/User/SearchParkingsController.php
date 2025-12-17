<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\User;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\User\SearchParkings\SearchParkings;
use App\UseCase\User\SearchParkings\SearchParkingsRequest;
use Twig\Environment;

class SearchParkingsController extends AbstractController
{
  public function __construct(
    private SearchParkings $useCase,
    private PresenterFactory $presenterFactory,
    private Environment $twig
  ) {}

  public function __invoke(): void
  {

    // 1. Récupération des paramètres
    $this->ensureIsUser();

    $lat = $_GET['lat'] ?? null;
    $lon = $_GET['lon'] ?? null;
    $radius = $_GET['radius'] ?? 15.0;

    // --- SCÉNARIO 1 : Affichage du Formulaire (Pas de coordonnées) ---
    if ($lat === null || $lon === null) {

      // Si l'utilisateur demande du JSON sans paramètre, c'est une erreur 400
      if ($this->wantsJson()) {
        $this->sendError("Latitude et Longitude requises", 400);
        return;
      }

      // Sinon (HTML), on affiche simplement le formulaire vide
      echo $this->twig->render('user/search_parkings_form.html.twig');
      return;
    }

    // --- SCÉNARIO 2 : Traitement de la Recherche (Coordonnées présentes) ---
    try {
      // A. Exécution du Use Case
      $request = new SearchParkingsRequest((float)$lat, (float)$lon, (float)$radius);
      $response = $this->useCase->execute($request);

      // B. Choix du Présentateur (JSON vs HTML) via ta Factory
      // Note: Tu devras ajouter 'User\SearchParkings' dans ta Factory
      $presenter = $this->presenterFactory->create('User\\SearchParkings');

      if ($this->wantsJson()) {
        header('Content-Type: application/json');
      }

      // C. Affichage du résultat
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
      // En HTML, on réaffiche le formulaire avec l'erreur
      echo $this->twig->render('user/search_parkings_form.html.twig', [
        'error' => $e->getMessage()
      ]);
    }
  }
}
