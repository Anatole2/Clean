<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\User\EnterParking;

use App\Domain\Entity\ParkingSession;
use App\Domain\Entity\Reservation;
use App\Domain\Entity\UserSubscription;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\Infrastructure\Service\RamseyIdGenerator;
use App\UseCase\User\EnterParking\EnterParking;
use App\UseCase\User\EnterParking\EnterParkingRequest;
use App\UseCase\User\EnterParking\EnterParkingResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnterParkingTest extends TestCase
{
  private ParkingSessionRepositoryInterface|MockObject $sessionRepo;
  private ReservationRepositoryInterface|MockObject $reservationRepo;
  private UserSubscriptionRepositoryInterface|MockObject $subscriptionRepo;
  private RamseyIdGenerator|MockObject $idGenerator;
  private EnterParking $useCase;

  protected function setUp(): void
  {

    /** @var ParkingSessionRepositoryInterface&MockObject */
    $this->sessionRepo = $this->createMock(ParkingSessionRepositoryInterface::class);
    /** @var ReservationRepositoryInterface&MockObject */
    $this->reservationRepo = $this->createMock(ReservationRepositoryInterface::class);
    /** @var UserSubscriptionRepositoryInterface&MockObject */
    $this->subscriptionRepo = $this->createMock(UserSubscriptionRepositoryInterface::class);
    /** @var RamseyIdGenerator&MockObject */
    $this->idGenerator = $this->createMock(RamseyIdGenerator::class);

    $this->useCase = new EnterParking(
      $this->sessionRepo,
      $this->reservationRepo,
      $this->subscriptionRepo,
      $this->idGenerator
    );
  }

  public function testExecuteSuccessWithReservation(): void
  {
    // ARRANGE
    $request = new EnterParkingRequest('u1', 'p1');
    $this->idGenerator->method('generate')->willReturn('session-123');

    // 1. Pas de session en cours
    $this->sessionRepo->expects($this->once())
      ->method('findActiveByUser')
      ->with('u1')
      ->willReturn(null);

    // 2. Réservation trouvée
    $reservation = $this->createMock(Reservation::class);
    $reservation->method('getId')->willReturn('res-1');

    $this->reservationRepo->expects($this->once())
      ->method('findActiveForUser')
      ->with('u1', 'p1')
      ->willReturn($reservation);

    // 3. On s'attend à ce que la session soit sauvegardée
    $this->sessionRepo->expects($this->once())
      ->method('save')
      ->with($this->callback(function (ParkingSession $session) {
        return $session->getId() === 'session-123'
          && $session->getReservationId() === 'res-1';
      }));

    // ACT
    $response = $this->useCase->execute($request);

    // ASSERT
    $this->assertInstanceOf(EnterParkingResponse::class, $response);
    $this->assertEquals('session-123', $response->session->getId());
  }

  public function testExecuteSuccessWithSubscription(): void
  {
    // ARRANGE
    $request = new EnterParkingRequest('u1', 'p1');
    $this->idGenerator->method('generate')->willReturn('session-sub');

    // 1. Pas de session
    $this->sessionRepo->method('findActiveByUser')->willReturn(null);

    // 2. Pas de réservation
    $this->reservationRepo->method('findActiveForUser')->willReturn(null);

    // 3. Abonnement trouvé !
    $sub = $this->createMock(UserSubscription::class);
    $this->subscriptionRepo->expects($this->once())
      ->method('findActiveForUser')
      ->with('u1', 'p1')
      ->willReturn($sub);

    // ACT
    $response = $this->useCase->execute($request);

    // ASSERT
    $this->assertNull($response->session->getReservationId()); // Pas de résa liée
    $this->assertEquals('session-sub', $response->session->getId());
  }

  public function testExecuteThrowsIfAlreadyInParking(): void
  {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Vous êtes déjà stationné");

    // Simulation : L'utilisateur est déjà dans un parking
    $existingSession = $this->createMock(ParkingSession::class);
    $this->sessionRepo->method('findActiveByUser')->willReturn($existingSession);

    $this->useCase->execute(new EnterParkingRequest('u1', 'p1'));
  }

  public function testExecuteThrowsIfAccessRefused(): void
  {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Accès refusé");

    $this->sessionRepo->method('findActiveByUser')->willReturn(null);

    // Ni réservation, ni abonnement
    $this->reservationRepo->method('findActiveForUser')->willReturn(null);
    $this->subscriptionRepo->method('findActiveForUser')->willReturn(null);

    $this->useCase->execute(new EnterParkingRequest('u1', 'p1'));
  }
}
