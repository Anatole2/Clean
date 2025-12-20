<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\Owner\GetUnauthorizedParkers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\UseCase\Owner\GetUnauthorizedParkers\GetUnauthorizedParkers;
use App\UseCase\Owner\GetUnauthorizedParkers\GetUnauthorizedParkersRequest;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\Domain\Entity\Parking;
use App\Domain\Entity\ParkingSession;
use App\Domain\Entity\Reservation;
use App\Domain\Entity\UserSubscription;
use App\Domain\ValueObject\GpsCoordinates;
use App\Domain\ValueObject\PriceGrid;
use App\Domain\ValueObject\WeeklySchedule;
use DateTimeImmutable;

class GetUnauthorizedParkersTest extends TestCase
{
  private ParkingRepositoryInterface&MockObject $parkingRepo;
  private ParkingSessionRepositoryInterface&MockObject $sessionRepo;
  private ReservationRepositoryInterface&MockObject $resRepo;
  private UserSubscriptionRepositoryInterface&MockObject $subRepo;
  private GetUnauthorizedParkers $useCase;

  protected function setUp(): void
  {
    $this->parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
    $this->sessionRepo = $this->createMock(ParkingSessionRepositoryInterface::class);
    $this->resRepo = $this->createMock(ReservationRepositoryInterface::class);
    $this->subRepo = $this->createMock(UserSubscriptionRepositoryInterface::class);

    $this->useCase = new GetUnauthorizedParkers(
      $this->parkingRepo,
      $this->sessionRepo,
      $this->resRepo,
      $this->subRepo
    );
  }

  public function testExecuteFiltersSquattersCorrectly(): void
  {
    // ARRANGE
    $pid = 'p1';
    $now = new DateTimeImmutable();

    // 1. Mock Parking
    $parking = new Parking($pid, 'owner', 'P', new GpsCoordinates(0, 0), 10, new PriceGrid([60 => 100]), new WeeklySchedule([]));
    $this->parkingRepo->method('findById')->willReturn($parking);

    // 2. Trois sessions actives (voitures présentes)
    // S1 : A une réservation valide -> OK
    $s1 = $this->createMockSession('s1', 'u1', 'res-1');

    // S2 : N'a pas de résa, mais a un abonnement -> OK
    $s2 = $this->createMockSession('s2', 'u2', null);

    // S3 : N'a ni résa valide, ni abonnement -> SQUATTEUR
    $s3 = $this->createMockSession('s3', 'u3', 'res-expired');

    $this->sessionRepo->method('findActiveSessionsByParkingId')->willReturn([$s1, $s2, $s3]);

    // 3. Config des Mocks Réservation
    // Réservation Valide (pour S1)
    $resValid = $this->createMock(Reservation::class);
    $resValid->method('getEndTime')->willReturn($now->modify('+1 hour')); // Futur

    // Réservation Expirée (pour S3)
    $resExpired = $this->createMock(Reservation::class);
    $resExpired->method('getEndTime')->willReturn($now->modify('-1 hour')); // Passé

    // CORRECTION MAJEURE ICI : Utilisation de willReturnMap
    $this->resRepo->method('findById')
      ->willReturnMap([
        ['res-1', $resValid],        // Si arg='res-1', retourne $resValid
        ['res-expired', $resExpired] // Si arg='res-expired', retourne $resExpired
      ]);

    // 4. Config des Mocks Abonnement
    // On utilise returnCallback pour gérer la logique dynamique
    // (u2 a un abonnement, les autres non)
    $this->subRepo->method('findActiveForUser')
      ->willReturnCallback(function (string $userId, string $parkingId, $date) {
        if ($userId === 'u2') {
          return $this->createMock(UserSubscription::class);
        }
        return null;
      });

    // ACT
    $response = $this->useCase->execute(new GetUnauthorizedParkersRequest($pid, 'owner'));

    // ASSERT
    // Seul S3 doit ressortir car S1 a une résa et S2 a un abonnement
    $this->assertCount(1, $response->squatters);
    $this->assertEquals('s3', $response->squatters[0]->getId());
  }

  private function createMockSession(string $id, string $userId, ?string $resId): ParkingSession
  {
    /** @var ParkingSession&\PHPUnit\Framework\MockObject\MockObject $s */
    $s = $this->createMock(ParkingSession::class);
    $s->method('getId')->willReturn($id);
    $s->method('getUserId')->willReturn($userId);
    $s->method('getReservationId')->willReturn($resId);
    return $s;
  }
}
