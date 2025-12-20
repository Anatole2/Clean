<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\Owner\GetParkingRevenue;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\UseCase\Owner\GetParkingRevenue\GetParkingRevenue;
use App\UseCase\Owner\GetParkingRevenue\GetParkingRevenueRequest;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\Domain\Entity\Parking;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;
use Exception;

class GetParkingRevenueTest extends TestCase
{
  private ParkingRepositoryInterface&MockObject $parkingRepo;
  private ReservationRepositoryInterface&MockObject $resRepo;
  private UserSubscriptionRepositoryInterface&MockObject $subRepo;
  private GetParkingRevenue $useCase;

  protected function setUp(): void
  {
    $this->parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
    $this->resRepo = $this->createMock(ReservationRepositoryInterface::class);
    $this->subRepo = $this->createMock(UserSubscriptionRepositoryInterface::class);

    $this->useCase = new GetParkingRevenue(
      $this->parkingRepo,
      $this->resRepo,
      $this->subRepo
    );
  }

  public function testExecuteCalculatesTotalRevenueCorrectly(): void
  {
    // 1. ARRANGE
    $parkingId = 'p1';
    $ownerId = 'owner-ok';
    $month = 1; // Janvier
    $year = 2025;

    // Mock Parking
    $parking = new Parking(
      $parkingId,
      $ownerId,
      'Parking Cash',
      new GpsCoordinates(0, 0),
      10,

      // ✅ CORRECTION ICI : Une grille valide (1h = 1€)
      new PriceGrid([60 => 100]),

      new WeeklySchedule([])
    );
    $this->parkingRepo->method('findById')->willReturn($parking);

    // Mock Réservations (Ex: 150.00 €)
    // On utilise $this->anything() pour ne pas bloquer sur le format exact des dates dans ce test unitaire
    $this->resRepo->expects($this->once())
      ->method('calculateRevenue')
      ->with($parkingId, $this->anything(), $this->anything())
      ->willReturn(15000);

    // Mock Abonnements (Ex: 200.00 €)
    $this->subRepo->expects($this->once())
      ->method('calculateRevenue')
      ->with($parkingId, $this->anything(), $this->anything())
      ->willReturn(20000);

    // 2. ACT
    $request = new GetParkingRevenueRequest($parkingId, $ownerId, $month, $year);
    $response = $this->useCase->execute($request);

    // 3. ASSERT
    // Total = 15000 + 20000 = 35000
    $this->assertEquals(15000, $response->revenueReservations);
    $this->assertEquals(20000, $response->revenueSubscriptions);
    $this->assertEquals(35000, $response->totalRevenue);
    $this->assertEquals(1, $response->month);
    $this->assertEquals(2025, $response->year);
  }

  public function testExecuteThrowsIfAccessDenied(): void
  {
    $parking = new Parking(
      'p1',
      'owner-A',
      'Parking',
      new GpsCoordinates(0, 0),
      10,

      // ✅ CORRECTION ICI AUSSI
      new PriceGrid([60 => 100]),

      new WeeklySchedule([])
    );
    $this->parkingRepo->method('findById')->willReturn($parking);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Accès refusé");

    $this->useCase->execute(new GetParkingRevenueRequest('p1', 'owner-B', 1, 2025));
  }
}
