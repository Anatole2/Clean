<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter;

class PresenterFactory
{
  public function create(string $useCaseName): PresenterInterface
  {
    // 1. Détection du format (via Header Accept)
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isJson = str_contains($accept, 'application/json');

    $format = $isJson ? 'Json' : 'Html';

    // 2. Construction du nom de classe
    // Ex: App\Infrastructure\Presenter\CreateParking\JsonCreateParkingPresenter
    $className = "App\\Infrastructure\\Presenter\\{$useCaseName}\\{$format}{$useCaseName}Presenter";

    // 3. Fallback : Si le HTML n'existe pas, on force JSON
    if (!class_exists($className)) {
      $className = "App\\Infrastructure\\Presenter\\{$useCaseName}\\Json{$useCaseName}Presenter";
      header('Content-Type: application/json');
    } else {
      header('Content-Type: ' . ($isJson ? 'application/json' : 'text/html'));
    }

    if (!class_exists($className)) {
      throw new \Exception("Presenter non trouvé pour : $useCaseName");
    }

    return new $className();
  }
}
