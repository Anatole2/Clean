<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\User\GetParkingSessions;

use App\Domain\Entity\Parking;
use App\Domain\Entity\ParkingSession;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\UseCase\User\GetParkingSessions\GetParkingSessionsRequest;
use App\UseCase\User\GetParkingSessions\GetParkingSessions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class GetParkingSessionsTest extends TestCase
{
  private ParkingSessionRepositoryInterface&MockObject $sessionRepo;
  private ParkingRepositoryInterface&MockObject $parkingRepo;
  private GetParkingSessions $useCase;

  protected function setUp(): void
  {
    $this->sessionRepo = $this->createMock(ParkingSessionRepositoryInterface::class);
    $this->parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
    $this->useCase = new GetParkingSessions($this->sessionRepo, $this->parkingRepo);
  }

  public function testExecuteReturnsMappedSessionsDto(): void
  {
    // ARRANGE
    $userId = 'u1';
    $parkingId = 'p1';

    // 1. Session Terminée (2 heures)
    $sessionClosed = new ParkingSession(
      's1',
      $parkingId,
      $userId,
      null,
      new DateTimeImmutable('2023-01-01 10:00'),
      new DateTimeImmutable('2023-01-01 12:00'),
      550
    );

    // 2. Session En Cours (Commencée il y a 1 heure par rapport à maintenant)
    $startTime = new DateTimeImmutable('-1 hour');
    $sessionActive = new ParkingSession(
      's2',
      $parkingId,
      $userId,
      null,
      $startTime,
      null, // Pas encore sortie
      0
    );

    // Mocks
    $this->sessionRepo->expects($this->once())
      ->method('findByUserId')
      ->with($userId)
      ->willReturn([$sessionActive, $sessionClosed]); // Ordre simulé du repo

    // Mock Parking pour avoir le nom
    $parking = $this->createMock(Parking::class);
    $parking->method('getName')->willReturn('Grand Garage');

    $this->parkingRepo->method('findById')
      ->with($parkingId)
      ->willReturn($parking);

    // ACT
    $request = new GetParkingSessionsRequest($userId);
    $response = $this->useCase->execute($request);

    // ASSERT
    $this->assertCount(2, $response->sessions);

    // --- Vérification Session Active ---
    $dtoActive = $response->sessions[0];
    $this->assertEquals('s2', $dtoActive->id);
    $this->assertEquals('Grand Garage', $dtoActive->parkingName);
    $this->assertTrue($dtoActive->isActive);
    $this->assertEquals('EN COURS', $dtoActive->status);
    $this->assertNull($dtoActive->exitTime);
    // On vérifie que la durée contient "(en cours)"
    $this->assertStringContainsString('(en cours)', $dtoActive->duration);
    // On vérifie que le calcul est approximativement bon (1h)
    $this->assertStringContainsString('1 h', $dtoActive->duration);

    // --- Vérification Session Terminée ---
    $dtoClosed = $response->sessions[1];
    $this->assertEquals('s1', $dtoClosed->id);
    $this->assertFalse($dtoClosed->isActive);
    $this->assertEquals('TERMINÉ', $dtoClosed->status);
    $this->assertEquals('01/01/2023 12:00', $dtoClosed->exitTime);
    // Durée exacte 2h00
    $this->assertEquals('2 h 0 min', $dtoClosed->duration);
    $this->assertEquals(5.50, $dtoClosed->pricePaid); // 550 cents -> 5.50
  }

  public function testExecuteHandlesUnknownParking(): void
  {
    // ARRANGE
    $session = new ParkingSession(
      's1',
      'deleted-parking-id',
      'u1',
      null,
      new DateTimeImmutable(),
      null,
      0
    );

    $this->sessionRepo->method('findByUserId')->willReturn([$session]);

    // Le repo parking renvoie NULL
    $this->parkingRepo->method('findById')->willReturn(null);

    // ACT
    $response = $this->useCase->execute(new GetParkingSessionsRequest('u1'));

    // ASSERT
    $this->assertEquals('Parking inconnu', $response->sessions[0]->parkingName);
  }
}
