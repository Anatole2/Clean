<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\Owner\GetOwnerParkingSessions;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\UseCase\Owner\GetOwnerParkingSessions\GetOwnerParkingSessions;
use App\UseCase\Owner\GetOwnerParkingSessions\GetOwnerParkingSessionsRequest;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\Entity\Parking;
use App\Domain\Entity\ParkingSession;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;
use Exception;

class GetOwnerParkingSessionsTest extends TestCase
{
  private ParkingRepositoryInterface&MockObject $parkingRepo;
  private ParkingSessionRepositoryInterface&MockObject $sessionRepo;
  private GetOwnerParkingSessions $useCase;

  protected function setUp(): void
  {
    $this->parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
    $this->sessionRepo = $this->createMock(ParkingSessionRepositoryInterface::class);
    $this->useCase = new GetOwnerParkingSessions($this->sessionRepo, $this->parkingRepo);
  }

  public function testExecuteReturnsSessionsForOwner(): void
  {
    // 1. ARRANGE
    $parkingId = 'p1';
    $ownerId = 'owner-ok';

    // Mock Parking
    $parking = new Parking(
      $parkingId,
      $ownerId,
      'Test Parking',
      new GpsCoordinates(0, 0),
      10,
      new PriceGrid([60 => 100]),
      new WeeklySchedule([])
    );
    $this->parkingRepo->method('findById')->with($parkingId)->willReturn($parking);

    // Mock Sessions
    $sessions = [
      $this->createMock(ParkingSession::class),
      $this->createMock(ParkingSession::class)
    ];

    // On s'attend à ce que le repo soit appelé avec l'ID du parking
    $this->sessionRepo->expects($this->once())
      ->method('findByParkingId')
      ->with($parkingId)
      ->willReturn($sessions);

    // 2. ACT
    $request = new GetOwnerParkingSessionsRequest($parkingId, $ownerId);
    $response = $this->useCase->execute($request);

    // 3. ASSERT
    $this->assertSame($parking, $response->parking);
    $this->assertCount(2, $response->sessions);
  }

  public function testExecuteThrowsExceptionIfParkingNotFound(): void
  {
    $this->parkingRepo->method('findById')->willReturn(null);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Parking introuvable");

    $this->useCase->execute(new GetOwnerParkingSessionsRequest('bad-id', 'owner'));
  }

  public function testExecuteThrowsExceptionIfAccessDenied(): void
  {
    $parking = new Parking(
      'p1',
      'owner-A',
      'Test Parking',
      new GpsCoordinates(0, 0),
      10,
      new PriceGrid([60 => 100]),
      new WeeklySchedule([])
    );
    $this->parkingRepo->method('findById')->willReturn($parking);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Accès refusé");

    // Request faite par owner-B
    $this->useCase->execute(new GetOwnerParkingSessionsRequest('p1', 'owner-B'));
  }
}
