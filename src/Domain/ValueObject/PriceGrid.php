<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

class PriceGrid
{
  /** * @var array<int, int> Map : Durée (minutes) => Prix (centimes) 
   */
  private array $rates;

  /**
   * @param array<int, int> $ratesConfig Exemple: [30 => 60, 60 => 120] (30min = 60cts)
   */
  public function __construct(array $ratesConfig)
  {
    if (empty($ratesConfig)) {
      throw new InvalidArgumentException("La grille tarifaire ne peut pas être vide.");
    }

    // On s'assure que les tarifs sont triés par durée croissante (30min, 1h, 2h...)
    ksort($ratesConfig);
    $this->rates = $ratesConfig;
  }

  /**
   * Calcule le prix pour une durée donnée en respectant la règle :
   * "Toute tranche horaire entamée est due" 
   */
  public function calculatePrice(int $durationInMinutes): int
  {
    // Si la durée est nulle ou négative, c'est gratuit (ou erreur selon règle métier)
    if ($durationInMinutes <= 0) {
      return 0;
    }

    foreach ($this->rates as $threshold => $price) {
      // Si la durée utilisateur est inférieure ou égale au palier
      // Ex: Durée 31min pour palier 60min -> On entre ici
      if ($durationInMinutes <= $threshold) {
        return $price;
      }
    }

    // Si on dépasse tous les paliers (ex: resté 25h alors que la grille s'arrête à 24h)
    // On retourne le prix le plus élevé (plafond)
    return end($this->rates);
  }

  public function toArray(): array
  {
    return $this->rates;
  }
}
