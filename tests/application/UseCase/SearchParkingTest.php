<?php

namespace Tests\Application\UseCase;

use App\Application\UseCase\SearchParking;
use App\Domain\Entity\Parking;
use App\Domain\Repository\ParkingRepositoryInterface;
use PHPUnit\Framework\TestCase;

class SearchParkingTest extends TestCase
{
    public function testExecuteReturnsParkingsFoundByRepository(): void
    {
        // 1. Définition des données simulées (Mocks)
        $lat = 48.8584; // Coordonnées GPS (Paris)
        $lon = 2.2945;
        $debut = new \DateTimeImmutable('2025-12-05 10:00:00');
        $fin = new \DateTimeImmutable('2025-12-05 12:00:00');

        // Création d'objets Parking simulés
        // NOTE: Ces objets Parkings doivent être disponibles depuis le code de la Personne 2
        $parking1 = $this->createMock(Parking::class);
        $parking2 = $this->createMock(Parking::class);
        $expectedParkings = [$parking1, $parking2];

        // 2. Création du Mock du Repository
        // Nous allons simuler la dépendance de la couche Domaine.
        $parkingRepositoryMock = $this->createMock(ParkingRepositoryInterface::class);

        // Définir le comportement attendu sur l'interface
        // Nous nous assurons que la méthode findAvailableNearGps est appelée UNE FOIS avec les bons arguments
        $parkingRepositoryMock
            ->expects($this->once())
            ->method('findAvailableNearGps')
            ->with($lat, $lon, $debut, $fin)
            ->willReturn($expectedParkings); // Le mock va retourner nos parkings simulés

        // 3. Exécution du Use Case
        $searchParking = new SearchParking($parkingRepositoryMock);
        $result = $searchParking->execute($lat, $lon, $debut, $fin);

        // 4. Assertion (Vérification)
        $this->assertSame($expectedParkings, $result, 'Le Use Case doit retourner la liste exacte fournie par le Repository.');
    }

    public function testExecuteReturnsEmptyArrayWhenNoParkingIsFound(): void
    {
        // 1. Définition des données
        $lat = 48.8584; 
        $lon = 2.2945;
        $debut = new \DateTimeImmutable('2025-12-05 10:00:00');
        $fin = new \DateTimeImmutable('2025-12-05 12:00:00');

        $expectedParkings = [];

        // 2. Création du Mock du Repository
        $parkingRepositoryMock = $this->createMock(ParkingRepositoryInterface::class);
        
        // Configuration pour renvoyer un tableau vide
        $parkingRepositoryMock
            ->expects($this->once())
            ->method('findAvailableNearGps')
            ->willReturn($expectedParkings);

        // 3. Exécution du Use Case
        $searchParking = new SearchParking($parkingRepositoryMock);
        $result = $searchParking->execute($lat, $lon, $debut, $fin);

        // 4. Assertion (Vérification)
        $this->assertIsArray($result, 'Le résultat doit être un tableau.');
        $this->assertEmpty($result, 'Le résultat doit être un tableau vide.');
    }
}