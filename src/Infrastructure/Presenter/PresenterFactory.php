<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter;

use Twig\Environment;

class PresenterFactory
{
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
    $className = "App\\Infrastructure\\Presenter\\{$useCaseName}\\{$format}Presenter";
    $shortName = array_slice(explode('\\', $useCaseName), -1)[0]; // Récupère "CreateParking" depuis "Owner\CreateParking"
    $className = "App\\Infrastructure\\Presenter\\{$useCaseName}\\{$format}{$shortName}Presenter";

    // 3. Fallback & Instanciation
    if (!class_exists($className)) {
      $format = 'Json';
      $className = "App\\Infrastructure\\Presenter\\{$useCaseName}\\Json{$shortName}Presenter";

      if (!class_exists($className)) {
        throw new \Exception("Presenter non trouvé : $className");
      }
    }

    // 4. Headers et Retour
    if ($format === 'Json') {
      header('Content-Type: application/json');
      return new $className();
    } else {
      header('Content-Type: text/html; charset=utf-8');
      return new $className($this->twig);
    }
  }
}
