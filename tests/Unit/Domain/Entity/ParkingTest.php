<?php

namespace Tests\Unit\Domain\Entity;

use PHPUnit\Framework\TestCase;
use App\Domain\Entity\Parking;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;
use DateTimeImmutable;

class ParkingTest extends TestCase
{
  public function testParkingEntityStoresAndDelegatesCorrectly(): void
  {
    // 1. Préparation des dépendances (Value Objects)
    $gps = new GpsCoordinates(48.85, 2.35);
    $priceGrid = new PriceGrid([60 => 200]);
    $schedule = new WeeklySchedule([
      ['startDay' => 1, 'startTime' => '08:00', 'endDay' => 1, 'endTime' => '18:00']
    ]);

    // 2. Création de l'Entité
    $parking = new Parking(
      'uuid-123',
      'owner-456',
      'Parking Test',
      $gps,
      50,
      $priceGrid,
      $schedule
    );

    // 3. Vérification des Données simples (Getters manquants ajoutés ici)
    $this->assertEquals('uuid-123', $parking->getId());
    $this->assertEquals('owner-456', $parking->getOwnerId()); // Ajouté
    $this->assertEquals('Parking Test', $parking->getName());
    $this->assertEquals(50, $parking->getTotalPlaces());

    // 4. Vérification des Objets (On vérifie que ce sont bien les mêmes instances)
    $this->assertSame($gps, $parking->getCoordinates()); // Ajouté
    $this->assertSame($priceGrid, $parking->getPriceGrid()); // Ajouté
    $this->assertSame($schedule, $parking->getOpeningHours()); // Ajouté

    // 5. Vérification de la Logique Métier (Délégation)
    $this->assertEquals(200, $parking->calculatePrice(45));

    $this->assertTrue($parking->isOpen(new DateTimeImmutable('Monday 10:00')));
    $this->assertFalse($parking->isOpen(new DateTimeImmutable('Sunday 10:00')));
  }
}
