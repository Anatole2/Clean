<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\Shared\GetParkingDetails;

use App\Domain\Entity\Parking;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\UseCase\Shared\GetParkingDetails\GetParkingDetails;
use App\UseCase\Shared\GetParkingDetails\GetParkingDetailsRequest;
use App\UseCase\Shared\GetParkingDetails\GetParkingDetailsResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetParkingDetailsTest extends TestCase
{
  private ParkingRepositoryInterface|MockObject $repository;
  private GetParkingDetails $useCase;

  protected function setUp(): void
  {
    // On mocke le repository car on ne veut pas appeler la vraie BDD
    $repository = $this->repository = $this->createMock(ParkingRepositoryInterface::class);

    /** @var ParkingRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repository */
    $this->useCase = new GetParkingDetails($repository);
  }

  public function testExecuteReturnsParkingWhenFound(): void
  {
    // ARRANGE
    $parkingId = 'uuid-123';
    $request = new GetParkingDetailsRequest($parkingId);

    // On simule un Parking trouvé (un Mock suffit ici, pas besoin d'un vrai objet complexe)
    $parkingMock = $this->createMock(Parking::class);

    // On configure le repo : "Quand on te demande l'ID uuid-123, renvoie ce parking"
    $this->repository->expects($this->once())
      ->method('findById')
      ->with($parkingId)
      ->willReturn($parkingMock);

    // ACT
    $response = $this->useCase->execute($request);

    // ASSERT
    $this->assertInstanceOf(GetParkingDetailsResponse::class, $response);
    $this->assertSame($parkingMock, $response->parking);
  }

  public function testExecuteThrowsExceptionWhenParkingNotFound(): void
  {
    // ASSERT (On prépare l'attente de l'erreur)
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Parking introuvable.");

    // ARRANGE
    $request = new GetParkingDetailsRequest('unknown-id');

    // On configure le repo pour renvoyer NULL
    $this->repository->expects($this->once())
      ->method('findById')
      ->with('unknown-id')
      ->willReturn(null);

    // ACT
    $this->useCase->execute($request);
  }
}
