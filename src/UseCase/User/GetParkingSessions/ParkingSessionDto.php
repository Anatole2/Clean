<?php

declare(strict_types=1);

namespace App\UseCase\User\GetParkingSessions;

class ParkingSessionDto
{
  public function __construct(
    public string $id,
    public string $parkingName,
    public string $entryTime,
    public ?string $exitTime,   // Null si encore dans le parking
    public string $duration,    // Calculée (différence entrée/sortie ou entrée/maintenant)
    public float $pricePaid,    // En Euros
    public string $status,      // 'EN COURS' ou 'TERMINÉ'
    public bool $isActive       // Booléen pratique pour le front (affichage badge vert)
  ) {}
}
