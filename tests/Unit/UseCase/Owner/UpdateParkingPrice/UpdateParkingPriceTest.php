<?php

namespace Tests\Unit\UseCase\Owner\UpdateParkingPrice;

use PHPUnit\Framework\TestCase;
use App\UseCase\Owner\UpdateParkingPrice\UpdateParkingPrice;
use App\UseCase\Owner\UpdateParkingPrice\UpdateParkingPriceRequest;
use App\UseCase\Owner\UpdateParkingPrice\UpdateParkingPriceResponse;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Entity\Parking;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;
use Exception;

class UpdateParkingPriceTest extends TestCase
{
  public function testItUpdatesPriceGridSuccessfully(): void
  {
    // 1. ARRANGEMENT

    // On crée un parking existant avec un tarif à 1€ (100 cts)
    $existingParking = new Parking(
      'uuid-123',
      'owner-correct', // Le vrai propriétaire
      'Parking Test',
      new GpsCoordinates(48.0, 2.0),
      50,
      new PriceGrid([60 => 100]), // Ancien prix : 1h = 1€
      new WeeklySchedule([])
    );

    // On Mocke le repository
    $repoMock = $this->createMock(ParkingRepositoryInterface::class);

    // Quand on cherchera l'ID, on trouvera notre parking
    $repoMock->method('findById')->willReturn($existingParking);

    // CRITIQUE : On s'attend à ce que 'save' soit appelé une fois
    // et on vérifie que l'objet sauvegardé contient bien le NOUVEAU prix
    $repoMock->expects($this->once())
      ->method('save')
      ->with($this->callback(function (Parking $p) {
        // On vérifie que le prix pour 60min est passé à 2€ (200)
        return $p->calculatePrice(60) === 200;
      }));

    /** @var ParkingRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repoMock */
    // La requête avec le nouveau tarif (2€)
    $request = new UpdateParkingPriceRequest(
      parkingId: 'uuid-123',
      ownerId: 'owner-correct',
      newPriceGridConfig: [60 => 200]
    );


    // 2. ACTION
    $useCase = new UpdateParkingPrice($repoMock);
    $response = $useCase->execute($request);

    // 3. ASSERTION
    $this->assertInstanceOf(UpdateParkingPriceResponse::class, $response);
    $updatedParking = $response->parking; // On récupère l'entité
    $priceGridArray = $updatedParking->getPriceGrid()->toArray(); // On récupère le tableau via le VO
    $this->assertEquals(200, $priceGridArray[60]);
  }

  public function testItThrowsExceptionIfParkingNotFound(): void
  {
    // 1. Le repo renvoie null
    $repoMock = $this->createMock(ParkingRepositoryInterface::class);
    $repoMock->method('findById')->willReturn(null);

    /** @var ParkingRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repoMock */
    // 2. On s'attend à une erreur
    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Parking introuvable");

    // 3. Exécution
    $useCase = new UpdateParkingPrice($repoMock);
    $useCase->execute(new UpdateParkingPriceRequest('bad-id', 'owner', []));
  }

  public function testItThrowsExceptionIfUserIsNotOwner(): void
  {
    // 1. Un parking appartenant à "owner-A"
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
    /** @var ParkingRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repoMock */
    // 2. Une requête faite par "owner-B" (le pirate)
    $request = new UpdateParkingPriceRequest(
      parkingId: 'id',
      ownerId: 'owner-B', // <--- PIRATE !
      newPriceGridConfig: [60 => 500]
    );

    // 3. On veut que ça bloque
    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Accès refusé");

    $useCase = new UpdateParkingPrice($repoMock);
    $useCase->execute($request);
  }
}
