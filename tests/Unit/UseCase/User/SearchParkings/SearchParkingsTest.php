<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\User\SearchParkings;

use App\Domain\Entity\Parking;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\UseCase\User\SearchParkings\SearchParkings;
use App\UseCase\User\SearchParkings\SearchParkingsRequest;
use App\UseCase\User\SearchParkings\SearchParkingsResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use App\UseCase\User\SearchParkings\SearchParkingsResult;

class SearchParkingsTest extends TestCase
{
  private ParkingRepositoryInterface|MockObject $repository;
  private SearchParkings $useCase;

  protected function setUp(): void
  {
    // On mock le repository car on ne veut pas appeler la vraie BDD
    $repository = $this->repository = $this->createMock(ParkingRepositoryInterface::class);

    /** @var ParkingRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repository */
    $this->useCase = new SearchParkings($repository);
  }

  public function testExecuteReturnsEmptyListWhenNoParkingFound(): void
  {
    // ARRANGE
    // On attend un appel à findNearby qui retourne un tableau vide
    $this->repository
      ->expects($this->once())
      ->method('findNearby')
      ->willReturn([]);

    $request = new SearchParkingsRequest(48.8566, 2.3522, 10.0);

    // ACT
    $response = $this->useCase->execute($request);

    // ASSERT
    $this->assertInstanceOf(SearchParkingsResponse::class, $response);
    $this->assertEmpty($response->parkings);
  }

  public function testExecuteReturnsMappedParkingsWithFormattedPrice(): void
  {
    // ARRANGE
    $lat = 48.8566;
    $lon = 2.3522;
    $request = new SearchParkingsRequest($lat, $lon, 15.0);

    // 1. Préparation du Mock PriceGrid
    // On veut simuler que le prix pour 60min est de 2.50€ (250 centimes)
    $priceGrid = $this->createMock(PriceGrid::class);
    $priceGrid->method('calculatePrice')
      ->with(60) // Le UseCase demande le prix pour 60 min
      ->willReturn(250);

    // 2. Préparation du Mock Parking
    $parking = $this->createMock(Parking::class);
    $parking->method('getId')->willReturn('uuid-123');
    $parking->method('getName')->willReturn('Parking Central');
    $parking->method('getTotalPlaces')->willReturn(50);
    $parking->method('getCoordinates')->willReturn(new GpsCoordinates($lat, $lon));
    $parking->method('getPriceGrid')->willReturn($priceGrid);

    // 3. Configuration du Repository
    $this->repository
      ->expects($this->once())
      ->method('findNearby')
      ->with(
        $this->callback(fn($geo) => $geo instanceof GpsCoordinates),
        15.0 // Vérifie que le rayon est bien transmis
      )
      ->willReturn([$parking]);

    // ACT
    $response = $this->useCase->execute($request);

    // ASSERT
    $this->assertCount(1, $response->parkings);
    $result = $response->parkings[0];

    $this->assertEquals('uuid-123', $result->id);
    $this->assertEquals('Parking Central', $result->name);
    $this->assertEquals(50, $result->totalPlaces);

    // Vérification du formatage du prix (250 cents / 100 = 2.50)
    $this->assertEquals('2.50 € / 1h', $result->priceLabel);
  }

  public function testExecuteFormatsFreeParkingCorrectly(): void
  {
    // ARRANGE
    $request = new SearchParkingsRequest(48.85, 2.35);

    // Mock PriceGrid qui retourne 0 centime pour 60min
    $priceGrid = $this->createMock(PriceGrid::class);
    $priceGrid->method('calculatePrice')->with(60)->willReturn(0);

    $parking = $this->createMock(Parking::class);
    $parking->method('getId')->willReturn('p-free');
    $parking->method('getName')->willReturn('Parking Gratuit');
    $parking->method('getCoordinates')->willReturn(new GpsCoordinates(48.85, 2.35));
    $parking->method('getTotalPlaces')->willReturn(10);
    $parking->method('getPriceGrid')->willReturn($priceGrid);

    $this->repository->method('findNearby')->willReturn([$parking]);

    // ACT
    $response = $this->useCase->execute($request);

    // ASSERT
    // Vérification spécifique du label "Gratuit"
    $this->assertEquals('Gratuit 1h', $response->parkings[0]->priceLabel);
  }

  public function testExecuteThrowsExceptionIfCoordinatesAreInvalid(): void
  {
    // Ce test vérifie que le UseCase instancie bien GpsCoordinates
    // et que si les données sont invalides, l'exception remonte.

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("La latitude doit être comprise entre -90 et 90 degrés.");

    // Latitude 100 est impossible -> GpsCoordinates va throw l'exception
    $request = new SearchParkingsRequest(100.0, 2.35);

    $this->useCase->execute($request);
  }
  public function testRequestThrowsExceptionIfRadiusIsInvalid(): void
  {
    // ASSERT
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("Le rayon de recherche doit être positif.");

    // ACT : On tente de créer une request avec un rayon de -5 km
    new SearchParkingsRequest(48.85, 2.35, -5.0);
  }
}
