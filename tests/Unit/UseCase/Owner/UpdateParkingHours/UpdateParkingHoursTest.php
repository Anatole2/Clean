<?php

namespace Tests\Unit\UseCase\Owner\UpdateParkingHours;

use PHPUnit\Framework\TestCase;
use App\UseCase\Owner\UpdateParkingHours\UpdateParkingHours;
use App\UseCase\Owner\UpdateParkingHours\UpdateParkingHoursRequest;
use App\UseCase\Owner\UpdateParkingHours\UpdateParkingHoursResponse;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Entity\Parking;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;
use Exception;

class UpdateParkingHoursTest extends TestCase
{
  public function testItUpdatesOpeningHoursSuccessfully(): void
  {
    // 1. ARRANGEMENT

    // On prépare la nouvelle config : Lundi de 09h00 à 17h00
    $newScheduleConfig = [
      ['startDay' => 1, 'startTime' => '09:00', 'endDay' => 1, 'endTime' => '17:00']
    ];

    // On crée un parking existant (Initialement ouvert 24/7 = tableau vide)
    $existingParking = new Parking(
      'uuid-123',
      'owner-correct',
      'Parking Test',
      new GpsCoordinates(48.85, 2.35),
      50,
      new PriceGrid([60 => 100]),
      new WeeklySchedule([]) // <--- Ancien horaire
    );

    // Mock du Repository
    $repoMock = $this->createMock(ParkingRepositoryInterface::class);
    $repoMock->method('findById')->willReturn($existingParking);

    // CRITIQUE : On vérifie que la méthode save est appelée avec un parking
    // dont les horaires correspondent bien à la nouvelle config
    $repoMock->expects($this->once())
      ->method('save')
      ->with($this->callback(function (Parking $p) use ($newScheduleConfig) {
        // On compare les tableaux bruts
        return $p->getOpeningHours()->toArray() === $newScheduleConfig;
      }));

    // Création de la requête
    $request = new UpdateParkingHoursRequest(
      parkingId: 'uuid-123',
      ownerId: 'owner-correct',
      newOpeningHoursConfig: $newScheduleConfig
    );

    // 2. ACTION
    $useCase = new UpdateParkingHours($repoMock);
    $response = $useCase->execute($request);

    // 3. ASSERTION
    $this->assertInstanceOf(UpdateParkingHoursResponse::class, $response);
    $this->assertEquals('uuid-123', $response->id);
    $this->assertSame($newScheduleConfig, $response->openingHours);
  }

  public function testItThrowsExceptionIfParkingNotFound(): void
  {
    // 1. Le repository ne trouve rien
    $repoMock = $this->createMock(ParkingRepositoryInterface::class);
    $repoMock->method('findById')->willReturn(null);

    // 2. On s'attend à une erreur
    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Parking introuvable");

    // 3. Exécution
    $useCase = new UpdateParkingHours($repoMock);
    $useCase->execute(new UpdateParkingHoursRequest('bad-id', 'owner', []));
  }

  public function testItThrowsExceptionIfUserIsNotOwner(): void
  {
    // 1. Le parking appartient à owner-A
    $parking = new Parking(
      'id',
      'owner-A',
      'Name',
      new GpsCoordinates(0, 0),
      10,
      new PriceGrid([60 => 1]),
      new WeeklySchedule([])
    );

    $repoMock = $this->createMock(ParkingRepositoryInterface::class);
    $repoMock->method('findById')->willReturn($parking);

    // 2. La requête est faite par owner-B (PIRATE)
    $request = new UpdateParkingHoursRequest(
      parkingId: 'id',
      ownerId: 'owner-B',
      newOpeningHoursConfig: []
    );

    // 3. On veut que ça bloque
    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Accès refusé"); // Ou le message exact que tu as mis dans ton UseCase

    $useCase = new UpdateParkingHours($repoMock);
    $useCase->execute($request);
  }
}
