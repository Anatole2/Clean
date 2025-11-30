<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Parking;
use App\Domain\ValueObject\GpsCoordinates;

interface ParkingRepositoryInterface
{
  /**
   * Persistance : Création ou Mise à jour
   */
  public function save(Parking $parking): void;

  /**
   * Suppression
   */
  public function delete(string $id): void;

  /**
   * Lecture par ID unique
   */
  public function findById(string $id): ?Parking;

  /**
   * Lecture de tous les parkings
   * @return Parking[]
   */
  public function findAll(): array;

  /**
   * Filtre par Propriétaire (Requis pour l'espace Owner [cite: 119])
   * @return Parking[]
   */
  public function findByOwnerId(string $ownerId): array;

  /**
   * Recherche géolocalisée (Requis pour l'espace User )
   * Cette méthode devra retourner les parkings dans un rayon donné.
   * * @param GpsCoordinates $center Le point central de la recherche
   * @param float $radiusInKm Le rayon de recherche (ex: 5 km)
   * @return Parking[]
   */
  public function findNearby(GpsCoordinates $center, float $radiusInKm): array;
}
