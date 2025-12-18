<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\User\CreateReservation;

use App\Domain\Entity\Parking;
use App\Domain\Entity\UserSubscription;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\Domain\ValueObject\PriceGrid;
use App\Infrastructure\Service\RamseyIdGenerator;
use App\UseCase\User\CreateReservation\CreateReservation;
use App\UseCase\User\CreateReservation\CreateReservationRequest;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateReservationTest extends TestCase
{
  private ParkingRepositoryInterface|MockObject $parkingRepo;
  private ReservationRepositoryInterface|MockObject $reservationRepo;
  private UserSubscriptionRepositoryInterface|MockObject $subscriptionRepo;
  private RamseyIdGenerator|MockObject $idGenerator;
  private CreateReservation $useCase;

  protected function setUp(): void
  {
    /** @var ParkingRepositoryInterface&MockObject */
    $this->parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
    /** @var ReservationRepositoryInterface&MockObject */
    $this->reservationRepo = $this->createMock(ReservationRepositoryInterface::class);
    /** @var UserSubscriptionRepositoryInterface&MockObject */
    $this->subscriptionRepo = $this->createMock(UserSubscriptionRepositoryInterface::class);
    /** @var RamseyIdGenerator&MockObject */
    $this->idGenerator = $this->createMock(RamseyIdGenerator::class);

    $this->useCase = new CreateReservation(
      $this->parkingRepo,
      $this->reservationRepo,
      $this->subscriptionRepo,
      $this->idGenerator
    );
  }

  public function testExecuteCreatesReservationWhenSpotsAvailable(): void
  {
    // ARRANGE
    $start = new DateTimeImmutable('+1 hour');
    $end = new DateTimeImmutable('+2 hours');
    $request = new CreateReservationRequest('u1', 'p1', $start, $end);

    // 1. Mock Parking (10 places, Prix 2€)
    $parking = $this->createMock(Parking::class);
    $parking->method('getTotalPlaces')->willReturn(10);
    $schedule = $this->createMock(\App\Domain\ValueObject\WeeklySchedule::class);
    $schedule->method('isOpen')->willReturn(true);
    $parking->method('getOpeningHours')->willReturn($schedule);
    $priceGrid = $this->createMock(PriceGrid::class);
    $priceGrid->method('calculatePrice')->willReturn(200);
    $parking->method('getPriceGrid')->willReturn($priceGrid);
    $parking->method('getTotalPlaces')->willReturn(10);

    $this->parkingRepo->method('findById')->willReturn($parking);

    // 2. Mock Disponibilité
    // 2 résas existantes
    $this->reservationRepo->method('countOverlappingReservations')->willReturn(2);
    // 0 abonnements
    $this->subscriptionRepo->method('findActiveOverlappingRange')->willReturn([]);

    // 3. Mock ID
    $this->idGenerator->method('generate')->willReturn('new-uuid');

    // EXPECTS (On vérifie que save est bien appelé)
    $this->reservationRepo->expects($this->once())->method('save');

    // ACT
    $response = $this->useCase->execute($request);

    // ASSERT
    $this->assertEquals('new-uuid', $response->reservation->getId());
    $this->assertEquals(200, $response->reservation->getPricePaidInCents());
  }

  public function testExecuteThrowsExceptionWhenParkingIsFull(): void
  {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Le parking est complet pour ce créneau.");

    $start = new DateTimeImmutable('+1 hour');
    $end = new DateTimeImmutable('+2 hours');
    $request = new CreateReservationRequest('u1', 'p1', $start, $end);

    // 1. Mock Parking (Total 5 places)
    $parking = $this->createMock(Parking::class);
    $schedule = $this->createMock(\App\Domain\ValueObject\WeeklySchedule::class);
    $schedule->method('isOpen')->willReturn(true); // <--- Important
    $parking->method('getOpeningHours')->willReturn($schedule);
    $parking->method('getTotalPlaces')->willReturn(5);
    $parking->method('getPriceGrid')->willReturn($this->createMock(PriceGrid::class)); // Pour éviter erreur sur null

    $this->parkingRepo->method('findById')->willReturn($parking);

    // 2. Mock Disponibilité : C'EST PLEIN !
    // 3 Réservations ponctuelles
    $this->reservationRepo->method('countOverlappingReservations')->willReturn(3);

    // + 2 Abonnements actifs = 5 places prises
    $sub1 = $this->createMock(UserSubscription::class);
    $sub1->method('occupiesSpotAt')->willReturn(true); // Il prend une place

    $sub2 = $this->createMock(UserSubscription::class);
    $sub2->method('occupiesSpotAt')->willReturn(true); // Il prend une place

    $this->subscriptionRepo->method('findActiveOverlappingRange')->willReturn([$sub1, $sub2]);

    // Total Occupé = 3 + 2 = 5. Capacité = 5.
    // Si j'essaie d'entrer => 6 > 5 => Exception.

    // ACT
    $this->useCase->execute($request);
  }
  public function testExecuteThrowsExceptionIfParkingNotFound(): void
  {
    // ASSERT
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Parking introuvable.");

    // ARRANGE
    $request = new CreateReservationRequest(
      'u1',
      'unknown-parking',
      new DateTimeImmutable('+1 hour'),
      new DateTimeImmutable('+2 hours')
    );

    // On configure le Mock pour renvoyer NULL
    $this->parkingRepo
      ->method('findById')
      ->with('unknown-parking')
      ->willReturn(null);

    // ACT
    $this->useCase->execute($request);
  }
  public function testItCanBeCreatedWithValidData(): void
  {
    $start = new DateTimeImmutable('+1 hour');
    $end = new DateTimeImmutable('+2 hours');

    $request = new CreateReservationRequest('u1', 'p1', $start, $end);

    $this->assertSame('u1', $request->userId);
    $this->assertSame('p1', $request->parkingId);
    $this->assertSame($start, $request->startTime);
    $this->assertSame($end, $request->endTime);
  }

  public function testItThrowsExceptionIfEndIsBeforeStart(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("La date de fin doit être après la date de début.");

    $start = new DateTimeImmutable('+2 hours');
    $end = new DateTimeImmutable('+1 hour'); // Erreur : Fin avant début

    new CreateReservationRequest('u1', 'p1', $start, $end);
  }

  public function testItThrowsExceptionIfDateIsInThePast(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("Impossible de réserver dans le passé.");

    $start = new DateTimeImmutable('-1 hour'); // Erreur : Dans le passé
    $end = new DateTimeImmutable('+1 hour');

    new CreateReservationRequest('u1', 'p1', $start, $end);
  }
  public function testExecuteThrowsExceptionIfParkingIsClosed(): void
  {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Le parking est fermé sur les horaires demandés.");

    // ARRANGE
    $start = new DateTimeImmutable('+1 day 03:00');
    $end = new DateTimeImmutable('+1 day 05:00');
    $request = new CreateReservationRequest('u1', 'p1', $start, $end);

    // Mock du Parking et de son WeeklySchedule
    $parking = $this->createMock(Parking::class);
    $schedule = $this->createMock(\App\Domain\ValueObject\WeeklySchedule::class);

    // On dit que le parking est FERMÉ (isOpen return false)
    $schedule->method('isOpen')->willReturn(false);

    $parking->method('getOpeningHours')->willReturn($schedule);
    $this->parkingRepo->method('findById')->willReturn($parking);

    // ACT
    $this->useCase->execute($request);
  }
}
