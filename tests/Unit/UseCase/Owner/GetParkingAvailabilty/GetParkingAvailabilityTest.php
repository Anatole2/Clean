<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\Owner\GetParkingAvailability;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\UseCase\Owner\GetParkingAvailability\GetParkingAvailability;
use App\UseCase\Owner\GetParkingAvailability\GetParkingAvailabilityRequest;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\Entity\Parking;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;
use DateTimeImmutable;
use Exception;

class GetParkingAvailabilityTest extends TestCase
{
  private ParkingRepositoryInterface&MockObject $parkingRepo;
  private ReservationRepositoryInterface&MockObject $resRepo;
  private UserSubscriptionRepositoryInterface&MockObject $subRepo;
  private ParkingSessionRepositoryInterface&MockObject $sessionRepo;
  private GetParkingAvailability $useCase;

  protected function setUp(): void
  {
    // 1. Création des Mocks pour les 4 dépendances
    $this->parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
    $this->resRepo = $this->createMock(ReservationRepositoryInterface::class);
    $this->subRepo = $this->createMock(UserSubscriptionRepositoryInterface::class);
    $this->sessionRepo = $this->createMock(ParkingSessionRepositoryInterface::class);

    // 2. Injection dans le Use Case
    $this->useCase = new GetParkingAvailability(
      $this->parkingRepo,
      $this->resRepo,
      $this->subRepo,
      $this->sessionRepo
    );
  }

  public function testExecuteCalculatesAvailabilityCorrectly(): void
  {
    // ARRANGE
    $parkingId = 'p1';
    $ownerId = 'owner-ok';
    $checkTime = new DateTimeImmutable('2025-12-25 20:00:00');

    // Mock Parking : 100 places au total
    $parking = new Parking(
      $parkingId,
      $ownerId,
      'Parking Test',
      new GpsCoordinates(0, 0),
      100,
      new PriceGrid([60 => 100]),
      new WeeklySchedule([])
    );
    $this->parkingRepo->method('findById')->willReturn($parking);

    // Mock : 10 Réservations classiques
    $this->resRepo->expects($this->once())
      ->method('countActiveAt')
      ->with($parkingId, $checkTime)
      ->willReturn(10);

    // Mock : 5 Abonnements actifs
    $this->subRepo->expects($this->once())
      ->method('countActiveAt')
      ->with($parkingId, $checkTime)
      ->willReturn(5);

    // Mock : 2 Squatteurs (voitures ventouses)
    $this->sessionRepo->expects($this->once())
      ->method('countOverstayingCars')
      ->with($parkingId, $checkTime)
      ->willReturn(2);

    // ACT
    $request = new GetParkingAvailabilityRequest($parkingId, $ownerId, $checkTime);
    $response = $this->useCase->execute($request);

    // ASSERT
    // Calcul : 100 - (10 + 5 + 2) = 100 - 17 = 83
    $this->assertEquals(100, $response->totalPlaces);
    $this->assertEquals(10, $response->reservedPlaces);
    $this->assertEquals(5, $response->subscribedPlaces);
    $this->assertEquals(2, $response->squatterPlaces);
    $this->assertEquals(83, $response->availablePlaces);
  }

  public function testExecuteReturnsZeroAvailabilityIfOverbooked(): void
  {
    // Cas extrême : Plus de réservations que de places (bug système possible)
    // On veut s'assurer que ça ne retourne pas -5 places disponibles.

    $parking = new Parking(
      'p1',
      'owner-ok',
      'Small Parking',
      new GpsCoordinates(0, 0),
      10, // 10 Places
      new PriceGrid([60 => 100]),
      new WeeklySchedule([])
    );
    $this->parkingRepo->method('findById')->willReturn($parking);

    $this->resRepo->method('countActiveAt')->willReturn(15); // 15 Réservations !
    $this->subRepo->method('countActiveAt')->willReturn(0);
    $this->sessionRepo->method('countOverstayingCars')->willReturn(0);

    $request = new GetParkingAvailabilityRequest('p1', 'owner-ok', new DateTimeImmutable());
    $response = $this->useCase->execute($request);

    $this->assertEquals(0, $response->availablePlaces, "Ne doit pas être négatif");
  }

  public function testExecuteThrowsExceptionIfParkingNotFound(): void
  {
    $this->parkingRepo->method('findById')->willReturn(null);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Parking introuvable");

    $this->useCase->execute(new GetParkingAvailabilityRequest('bad-id', 'owner', new DateTimeImmutable()));
  }

  public function testExecuteThrowsExceptionIfAccessDenied(): void
  {
    $parking = new Parking(
      'p1',
      'owner-A',
      'Parking',
      new GpsCoordinates(0, 0),
      10,
      new PriceGrid([60 => 100]),
      new WeeklySchedule([])
    );
    $this->parkingRepo->method('findById')->willReturn($parking);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Accès refusé");

    $this->useCase->execute(new GetParkingAvailabilityRequest('p1', 'owner-B', new DateTimeImmutable()));
  }
}
