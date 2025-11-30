<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

class GpsCoordinates
{
  public function __construct(
    private float $latitude,
    private float $longitude
  ) {
    $this->validate();
  }

  private function validate(): void
  {
    // La latitude va de -90 (Pôle Sud) à +90 (Pôle Nord)
    if ($this->latitude < -90 || $this->latitude > 90) {
      throw new InvalidArgumentException("La latitude doit être comprise entre -90 et 90 degrés.");
    }

    // La longitude va de -180 (Ouest) à +180 (Est)
    if ($this->longitude < -180 || $this->longitude > 180) {
      throw new InvalidArgumentException("La longitude doit être comprise entre -180 et 180 degrés.");
    }
  }

  public function getLatitude(): float
  {
    return $this->latitude;
  }

  public function getLongitude(): float
  {
    return $this->longitude;
  }

  /**
   * Utile pour afficher ou comparer rapidement (ex: "48.85,2.35")
   */
  public function __toString(): string
  {
    return sprintf('%F,%F', $this->latitude, $this->longitude);
  }
}
