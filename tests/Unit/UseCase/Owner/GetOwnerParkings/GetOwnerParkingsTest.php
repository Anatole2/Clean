<?php

declare(strict_types=1);

namespace App\Tests\Unit\UseCase\Owner\GetOwnerParkings;

use App\Domain\Entity\Parking;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\GpsCoordinates;
use App\UseCase\Owner\GetOwnerParkings\GetOwnerParkings;
use App\UseCase\Owner\GetOwnerParkings\GetOwnerParkingsRequest;
use App\UseCase\Owner\GetOwnerParkings\GetOwnerParkingsResponse;
use PHPUnit\Framework\TestCase;

class GetOwnerParkingsTest extends TestCase
{
  public function testExecuteReturnsListOfParkingsData(): void
  {
    // 1. Préparation (Mock du Repository)
    $repository = $this->createMock(ParkingRepositoryInterface::class);

    /** @var ParkingRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repository */
    // On crée un faux parking pour simuler le retour de la BDD
    $parking = $this->createMock(Parking::class);
    $parking->method('getId')->willReturn('uuid-123');
    $parking->method('getName')->willReturn('Parking Test');
    $parking->method('getTotalPlaces')->willReturn(100);

    // Simulation du Value Object GPS
    $coords = new GpsCoordinates(48.85, 2.35);
    $parking->method('getCoordinates')->willReturn($coords);

    // Le repository doit être appelé avec l'ID du owner et retourner notre parking
    $repository->expects($this->once())
      ->method('findByOwnerId')
      ->with('owner-1')
      ->willReturn([$parking]);

    // 2. Exécution du Use Case
    // CORRECTION ICI : On passe $repository (qui contient le mock), pas $repoMock
    $useCase = new GetOwnerParkings($repository);

    $request = new GetOwnerParkingsRequest('owner-1');
    $response = $useCase->execute($request);

    // 3. Assertions (Vérifications)
    $this->assertInstanceOf(GetOwnerParkingsResponse::class, $response);
    $this->assertCount(1, $response->parkings);

    $data = $response->parkings[0];
    $this->assertEquals('uuid-123', $data['id']);
    $this->assertEquals('Parking Test', $data['name']);
    $this->assertEquals(100, $data['totalPlaces']);
    $this->assertEquals(48.85, $data['latitude']);
    $this->assertEquals(2.35, $data['longitude']);
  }

  public function testExecuteReturnsEmptyArrayWhenNoParkingsFound(): void
  {
    // Cas où le propriétaire n'a pas de parking
    $repository = $this->createMock(ParkingRepositoryInterface::class);
    /** @var ParkingRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repository */
    // On configure le mock
    $repository->method('findByOwnerId')->willReturn([]);

    // CORRECTION ICI : On passe $repository
    $useCase = new GetOwnerParkings($repository);

    $response = $useCase->execute(new GetOwnerParkingsRequest('owner-empty'));

    $this->assertEmpty($response->parkings);
  }
}
