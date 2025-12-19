<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\User\SubscribeToParkingPlan;

use App\Domain\Entity\Parking;
use App\Domain\Entity\UserSubscription;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\SubscriptionPlan;
use App\Domain\ValueObject\WeeklySchedule;
use App\Infrastructure\Service\RamseyIdGenerator;
use App\UseCase\User\SubscribeToParkingPlan\SubscribeToParkingPlan;
use App\UseCase\User\SubscribeToParkingPlan\SubscribeToParkingPlanRequest;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubscribeToParkingPlanTest extends TestCase
{
  private ParkingRepositoryInterface&MockObject $parkingRepository;
  private UserSubscriptionRepositoryInterface&MockObject $subscriptionRepository;
  private RamseyIdGenerator&MockObject $idGenerator;
  private SubscribeToParkingPlan $useCase;

  protected function setUp(): void
  {
    // 1. On Mock les dépendances
    $this->parkingRepository = $this->createMock(ParkingRepositoryInterface::class);
    $this->subscriptionRepository = $this->createMock(UserSubscriptionRepositoryInterface::class);
    $this->idGenerator = $this->createMock(RamseyIdGenerator::class);

    // 2. On instancie le Use Case avec les mocks
    $this->useCase = new SubscribeToParkingPlan(
      $this->parkingRepository,
      $this->subscriptionRepository,
      $this->idGenerator
    );
  }

  public function testExecuteSuccess(): void
  {
    // ARRANGE
    $planId = 'plan-123';
    $parkingId = 'parking-abc';
    $userId = 'user-007';
    $startDateStr = (new DateTimeImmutable('+1 day'))->format('Y-m-d'); // Demain

    // Création d'un parking fictif avec 1 plan
    $plan = new SubscriptionPlan($planId, 'Forfait Gold', 5000, new WeeklySchedule([]));
    $parking = $this->createDummyParking($parkingId, 10, [$plan]);

    // Configuration des Mocks
    $this->parkingRepository->expects($this->once())
      ->method('findById')
      ->with($parkingId)
      ->willReturn($parking);

    // On dit qu'il y a 0 abonnements actifs, donc de la place (10 places totales)
    $this->subscriptionRepository->expects($this->once())
      ->method('countActiveForParking')
      ->willReturn(0);

    $this->idGenerator->expects($this->once())
      ->method('generate')
      ->willReturn('sub-uuid-generated');

    $this->subscriptionRepository->expects($this->once())
      ->method('save')
      ->with($this->isInstanceOf(UserSubscription::class));

    $request = new SubscribeToParkingPlanRequest($userId, $parkingId, $planId, $startDateStr);

    // ACT
    $response = $this->useCase->execute($request);

    // ASSERT
    $this->assertInstanceOf(UserSubscription::class, $response->subscription);
    $this->assertEquals('sub-uuid-generated', $response->subscription->getId());
    $this->assertEquals($userId, $response->subscription->getUserId());
    $this->assertEquals('Forfait Gold', $response->subscription->getPlanName()); // Vérif Snapshot
    $this->assertEquals(5000, $response->subscription->getPrice());           // Vérif Snapshot
  }

  public function testExecuteThrowsIfParkingNotFound(): void
  {
    // Mock : Parking introuvable (null)
    $this->parkingRepository->method('findById')->willReturn(null);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Parking introuvable.");

    $request = new SubscribeToParkingPlanRequest('u1', 'bad-parking-id', 'p1', '2025-01-01');
    $this->useCase->execute($request);
  }

  public function testExecuteThrowsIfPlanNotFoundInParking(): void
  {
    // Mock : Parking trouvé, mais sans le plan demandé
    $parking = $this->createDummyParking('p1', 10, []); // Aucun plan
    $this->parkingRepository->method('findById')->willReturn($parking);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Le plan d'abonnement 'unknown-plan' n'existe pas pour ce parking.");

    $request = new SubscribeToParkingPlanRequest('u1', 'p1', 'unknown-plan', '2025-01-01');
    $this->useCase->execute($request);
  }

  public function testExecuteThrowsIfDateIsPast(): void
  {
    // Mock : Parking et Plan OK
    $plan = new SubscriptionPlan('plan-1', 'Name', 100, new WeeklySchedule([]));
    $parking = $this->createDummyParking('p1', 10, [$plan]);
    $this->parkingRepository->method('findById')->willReturn($parking);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("La date de début ne peut pas être dans le passé.");

    // Date : Hier
    $pastDate = (new DateTimeImmutable('-1 day'))->format('Y-m-d');
    $request = new SubscribeToParkingPlanRequest('u1', 'p1', 'plan-1', $pastDate);

    $this->useCase->execute($request);
  }

  public function testExecuteThrowsIfDateFormatInvalid(): void
  {
    $plan = new SubscriptionPlan('plan-1', 'Name', 100, new WeeklySchedule([]));
    $parking = $this->createDummyParking('p1', 10, [$plan]);
    $this->parkingRepository->method('findById')->willReturn($parking);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Format de date invalide");

    $request = new SubscribeToParkingPlanRequest('u1', 'p1', 'plan-1', 'not-a-date');
    $this->useCase->execute($request);
  }

  public function testExecuteThrowsIfCapacityReached(): void
  {
    // ARRANGE
    $planId = 'plan-vip';
    $parkingId = 'p-full';
    $totalPlaces = 5;

    $plan = new SubscriptionPlan($planId, 'VIP', 100, new WeeklySchedule([]));
    $parking = $this->createDummyParking($parkingId, $totalPlaces, [$plan]);

    $this->parkingRepository->method('findById')->willReturn($parking);

    // Mock : On dit qu'il y a déjà 5 abonnements actifs (donc Parking COMPLET pour les abonnés)
    $this->subscriptionRepository->expects($this->once())
      ->method('countActiveForParking')
      ->willReturn(5); // 5 actifs >= 5 places totales

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Impossible de souscrire : Le quota d'abonnements pour ce parking est atteint.");

    $futureDate = (new DateTimeImmutable('+1 day'))->format('Y-m-d');
    $request = new SubscribeToParkingPlanRequest('u1', $parkingId, $planId, $futureDate);

    // ACT
    $this->useCase->execute($request);
  }

  /**
   * Helper pour créer rapidement un objet Parking valide
   */
  private function createDummyParking(string $id, int $places, array $plans): Parking
  {
    return new Parking(
      $id,
      'owner-1',
      'Test Parking',
      new GpsCoordinates(0, 0),
      $places,
      new PriceGrid([60 => 100]),
      new WeeklySchedule([]),
      $plans
    );
  }
}
