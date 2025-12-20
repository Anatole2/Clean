<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\Owner\GetParkingReservations;

use App\Domain\Entity\Parking;
use App\Domain\Entity\Reservation;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\UseCase\Owner\GetParkingReservations\GetParkingReservationsRequest;
use App\UseCase\Owner\GetParkingReservations\GetParkingReservations;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Exception;

class GetParkingReservationsTest extends TestCase
{
  private ReservationRepositoryInterface&MockObject $resRepo;
  private ParkingRepositoryInterface&MockObject $parkingRepo;
  private GetParkingReservations $useCase;

  protected function setUp(): void
  {
    $this->resRepo = $this->createMock(ReservationRepositoryInterface::class);
    $this->parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
    $this->useCase = new GetParkingReservations($this->resRepo, $this->parkingRepo);
  }

  public function testExecuteReturnsReservationsForOwner(): void
  {
    // ARRANGE
    $parkingId = 'p1';
    $ownerId = 'owner-ok';

    // 1. Mock du Parking
    $parking = new Parking(
      $parkingId,
      $ownerId,
      'Parking Test',
      new GpsCoordinates(0, 0),
      10,

      // ✅ CORRECTION ICI : On met un prix valide pour ne pas planter le PriceGrid
      new PriceGrid([60 => 100]),

      new WeeklySchedule([])
    );
    $this->parkingRepo->method('findById')->with($parkingId)->willReturn($parking);

    // 2. Mock des Réservations
    $reservations = [
      $this->createMock(Reservation::class),
      $this->createMock(Reservation::class)
    ];
    $this->resRepo->expects($this->once())
      ->method('findByParkingId')
      ->with($parkingId)
      ->willReturn($reservations);

    // ACT
    $request = new GetParkingReservationsRequest($parkingId, $ownerId);
    $response = $this->useCase->execute($request);

    // ASSERT
    $this->assertSame($parking, $response->parking);
    $this->assertCount(2, $response->reservations);
  }

  public function testExecuteThrowsExceptionIfParkingNotFound(): void
  {
    $this->parkingRepo->method('findById')->willReturn(null);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Parking introuvable");

    $this->useCase->execute(new GetParkingReservationsRequest('bad-id', 'owner'));
  }

  public function testExecuteThrowsExceptionIfUserIsNotOwner(): void
  {
    // Parking appartenant à "owner-A"
    $parking = new Parking(
      'p1',
      'owner-A',
      'Parking Test',
      new GpsCoordinates(0, 0),
      10,

      // ✅ CORRECTION ICI AUSSI
      new PriceGrid([60 => 100]),

      new WeeklySchedule([])
    );
    $this->parkingRepo->method('findById')->willReturn($parking);

    // Requête faite par "owner-B"
    $request = new GetParkingReservationsRequest('p1', 'owner-B');

    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Accès refusé");

    $this->useCase->execute($request);
  }
}
