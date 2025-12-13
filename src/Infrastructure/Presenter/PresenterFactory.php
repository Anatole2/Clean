<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter;

use Twig\Environment;

class PresenterFactory
{
  // 👇 1. On injecte Twig ici pour pouvoir le distribuer plus tard
  public function __construct(
    private Environment $twig
  ) {}

  public function create(string $useCaseName): PresenterInterface
  {
    // 1. Détection du format
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isJson = str_contains($accept, 'application/json');

    $format = $isJson ? 'Json' : 'Html';

    // 2. Construction du nom de classe
    // Attention : Assure-toi que $useCaseName correspond bien à tes dossiers (ex: "Owner\CreateParking")
    $className = "App\\Infrastructure\\Presenter\\{$useCaseName}\\{$format}Presenter";
    // Note: J'ai simplifié le nommage ici, vérifie si tes fichiers s'appellent 
    // "HtmlCreateParkingPresenter" ou juste "HtmlPresenter" dans le dossier CreateParking.
    // Si tes fichiers s'appellent "HtmlCreateParkingPresenter", garde ta logique :
    $shortName = array_slice(explode('\\', $useCaseName), -1)[0]; // Récupère "CreateParking" depuis "Owner\CreateParking"
    $className = "App\\Infrastructure\\Presenter\\{$useCaseName}\\{$format}{$shortName}Presenter";

    // 3. Fallback & Instanciation
    if (!class_exists($className)) {
      // Si pas de HTML, on force le JSON
      $format = 'Json';
      $className = "App\\Infrastructure\\Presenter\\{$useCaseName}\\Json{$shortName}Presenter";

      if (!class_exists($className)) {
        throw new \Exception("Presenter non trouvé : $className");
      }
    }

    // 4. Headers et Retour
    if ($format === 'Json') {
      header('Content-Type: application/json');
      return new $className(); // Le JSON n'a pas besoin de Twig
    } else {
      header('Content-Type: text/html; charset=utf-8');
      // 👇 C'est ICI que la magie opère : on donne Twig au presenter HTML
      return new $className($this->twig);
    }
  }
}
