<?php

namespace Tests\Unit\UseCase\Owner\CreateParking;

use PHPUnit\Framework\TestCase;
use App\UseCase\Owner\CreateParking\CreateParking;
use App\UseCase\Owner\CreateParking\CreateParkingRequest;
use App\UseCase\Owner\CreateParking\CreateParkingResponse;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Service\IdGeneratorInterface;
use App\Domain\Entity\Parking;

class CreateParkingTest extends TestCase
{
  public function testItCreatesAParkingAndReturnsResponse(): void
  {
    // --- 1. ARRANGEMENT (Préparation des doublures) ---

    // A. On mocke le générateur d'ID
    // "Quand on t'appelle, réponds toujours 'uuid-1234'"
    $idGenerator = $this->createMock(IdGeneratorInterface::class);
    $idGenerator->method('generate')->willReturn('uuid-1234');

    // B. On mocke le Repository
    // "Je veux vérifier qu'on appelle bien ta méthode 'save' une fois"
    $repository = $this->createMock(ParkingRepositoryInterface::class);
    $repository->expects($this->once())
      ->method('save')
      ->with($this->isInstanceOf(Parking::class));

    // C. On prépare la requête (DTO d'entrée)
    $request = new CreateParkingRequest(
      ownerId: 'owner-555',
      name: 'Parking de Test',
      latitude: 48.85,
      longitude: 2.35,
      totalPlaces: 100,
      priceGridConfig: [60 => 200], // 1h = 2€
      openingHoursConfig: []        // 24/7
    );

    // --- 2. ACTION (Exécution du Use Case) ---

    $useCase = new CreateParking($repository, $idGenerator);
    $response = $useCase->execute($request);

    // --- 3. ASSERTION (Vérifications) ---

    // On vérifie que le retour est bien le DTO de réponse attendu
    $this->assertInstanceOf(CreateParkingResponse::class, $response);

    // On vérifie que les données sont correctes
    $this->assertEquals('uuid-1234', $response->id); // L'ID vient du mock
    $this->assertEquals('Parking de Test', $response->name);
    $this->assertEquals(100, $response->totalPlaces);

    // On vérifie que les ValueObjects ont bien été transformés en tableaux primitifs
    $this->assertIsArray($response->priceGrid);
    $this->assertEquals(200, $response->priceGrid[60]);
  }
}
