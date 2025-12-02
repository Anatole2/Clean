<?php

namespace Tests\Unit\UseCase\Owner\AddParkingSubscriptionPlan;

use PHPUnit\Framework\TestCase;
use App\UseCase\Owner\AddParkingSubscriptionPlan\AddParkingSubscriptionPlan;
use App\UseCase\Owner\AddParkingSubscriptionPlan\AddParkingSubscriptionPlanRequest;
use App\UseCase\Owner\AddParkingSubscriptionPlan\AddParkingSubscriptionPlanResponse;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Entity\Parking;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;

class AddParkingSubscriptionPlanTest extends TestCase
{
  public function testItAddsSubscriptionPlanSuccessfully(): void
  {
    // 1. ARRANGEMENT
    $existingParking = new Parking(
      'uuid-123',
      'owner-1',
      'Parking Test',
      new GpsCoordinates(48.85, 2.35),
      100,
      new PriceGrid([60 => 200]),
      new WeeklySchedule([]),
      [] // Aucun plan au début
    );

    $repoMock = $this->createMock(ParkingRepositoryInterface::class);
    $repoMock->method('findById')->willReturn($existingParking);

    // On vérifie que la méthode save est appelée avec un parking qui contient bien le nouveau plan
    $repoMock->expects($this->once())
      ->method('save')
      ->with($this->callback(function (Parking $p) {
        $plans = $p->getSubscriptionPlans();
        return count($plans) === 1
          && $plans[0]->getName() === "Forfait Nuit"
          && $plans[0]->getMonthlyPrice() === 5000;
      }));

    /** @var ParkingRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repoMock */

    // Requête
    $request = new AddParkingSubscriptionPlanRequest(
      parkingId: 'uuid-123',
      ownerId: 'owner-1',
      planName: "Forfait Nuit",
      monthlyPrice: 5000,
      ruleConfig: [['startDay' => 1, 'startTime' => '18:00', 'endDay' => 2, 'endTime' => '08:00']]
    );

    // 2. ACTION
    $useCase = new AddParkingSubscriptionPlan($repoMock);
    $response = $useCase->execute($request);

    // 3. ASSERTION
    $this->assertInstanceOf(AddParkingSubscriptionPlanResponse::class, $response);
    $this->assertCount(1, $response->subscriptionPlans);
    $this->assertEquals("Forfait Nuit", $response->subscriptionPlans[0]['name']);
  }
  public function testItThrowsExceptionIfParkingNotFound(): void
  {
    // 1. Le repository renvoie null (pas trouvé)
    $repoMock = $this->createMock(ParkingRepositoryInterface::class);
    $repoMock->method('findById')->willReturn(null);

    // 2. On s'attend à une erreur
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Parking introuvable");

    /** @var ParkingRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repoMock */

    $useCase = new AddParkingSubscriptionPlan($repoMock);

    // Requête bidon
    $request = new AddParkingSubscriptionPlanRequest('bad-id', 'owner', 'Plan', 100, []);

    $useCase->execute($request);
  }
  public function testItThrowsExceptionIfUserIsNotOwner(): void
  {
    // 1. Le parking appartient à "owner-A"
    $parking = new Parking(
      'id',
      'owner-A',
      'Name',
      new GpsCoordinates(0, 0),
      10,
      new PriceGrid([60 => 1]),
      new WeeklySchedule([]),
      []
    );

    $repoMock = $this->createMock(ParkingRepositoryInterface::class);
    $repoMock->method('findById')->willReturn($parking);

    // 2. La requête vient de "owner-B" (Pirate)
    $request = new AddParkingSubscriptionPlanRequest(
      'id',
      'owner-B', // <--- Mauvais propriétaire
      'Plan',
      100,
      []
    );

    // 3. On veut que ça bloque
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Accès refusé");

    /** @var ParkingRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repoMock */

    $useCase = new AddParkingSubscriptionPlan($repoMock);
    $useCase->execute($request);
  }
}
