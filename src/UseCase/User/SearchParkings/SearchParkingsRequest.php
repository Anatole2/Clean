<?php

declare(strict_types=1);

namespace App\UseCase\User\SearchParkings;

class SearchParkingsRequest
{
  public function __construct(
    public float $latitude,
    public float $longitude,
    public float $radiusInKm = 15.0
  ) {
    // On valide juste le rayon car c'est spécifique à la requête de recherche
    if ($this->radiusInKm <= 0) {
      throw new \InvalidArgumentException("Le rayon de recherche doit être positif.");
    }
  }
}
