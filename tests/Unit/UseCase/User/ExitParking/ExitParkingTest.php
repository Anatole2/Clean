<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\User\ExitParking;

use App\Domain\Entity\Parking;
use App\Domain\Entity\ParkingSession;
use App\Domain\Entity\Reservation;
use App\Domain\Entity\UserSubscription;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\UseCase\User\ExitParking\ExitParking;
use App\UseCase\User\ExitParking\ExitParkingRequest;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExitParkingTest extends TestCase
{
  private ParkingSessionRepositoryInterface|MockObject $sessionRepo;
  private ParkingRepositoryInterface|MockObject $parkingRepo;
  private ReservationRepositoryInterface|MockObject $reservationRepo;
  private UserSubscriptionRepositoryInterface|MockObject $subscriptionRepo;
  private ExitParking $useCase;

  protected function setUp(): void
  {
    /** @var ParkingSessionRepositoryInterface&MockObject */
    $this->sessionRepo = $this->createMock(ParkingSessionRepositoryInterface::class);
    /** @var ParkingRepositoryInterface&MockObject */
    $this->parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
    /** @var ReservationRepositoryInterface&MockObject */
    $this->reservationRepo = $this->createMock(ReservationRepositoryInterface::class);
    /** @var UserSubscriptionRepositoryInterface&MockObject */
    $this->subscriptionRepo = $this->createMock(UserSubscriptionRepositoryInterface::class);

    $this->useCase = new ExitParking(
      $this->sessionRepo,
      $this->parkingRepo,
      $this->reservationRepo,
      $this->subscriptionRepo
    );
  }

  public function testExecuteSuccessWithoutOverstay(): void
  {
    // ARRANGE : L'utilisateur a une réservation qui finit DANS LE FUTUR (+1 heure)
    $now = new DateTimeImmutable();
    $futureEnd = $now->modify('+1 hour');

    $session = new ParkingSession('sess-1', 'p1', 'u1', 'res-1', $now->modify('-1 hour'));

    // Mocks
    $this->sessionRepo->method('findActiveByUser')->willReturn($session);

    $reservation = $this->createMock(Reservation::class);
    $reservation->method('getEndTime')->willReturn($futureEnd); // Fin dans le futur
    $this->reservationRepo->method('findById')->willReturn($reservation);

    // Expects : La session doit être sauvegardée
    $this->sessionRepo->expects($this->once())->method('save');

    // ACT
    $response = $this->useCase->execute(new ExitParkingRequest('u1', 'p1'));

    // ASSERT
    $this->assertNotNull($response->session->getExitTime()); // La sortie est enregistrée
    $this->assertEquals(0, $response->overstayMinutes);      // Pas de dépassement
    $this->assertEquals(0, $response->extraCost);            // Rien à payer
    $this->assertFalse($response->penaltyApplied);
  }

  public function testExecuteWithOverstayAndPenalty(): void
  {
    // ARRANGE : La réservation est finie depuis 1 HEURE (Passé)
    $now = new DateTimeImmutable();
    $pastEnd = $now->modify('-60 minutes'); // Fini il y a 1h

    $session = new ParkingSession('sess-1', 'p1', 'u1', 'res-1', $now->modify('-3 hours'));

    // 1. Session trouvée
    $this->sessionRepo->method('findActiveByUser')->willReturn($session);

    // 2. Réservation trouvée (finie dans le passé)
    $reservation = $this->createMock(Reservation::class);
    $reservation->method('getEndTime')->willReturn($pastEnd);
    $this->reservationRepo->method('findById')->willReturn($reservation);

    // 3. Parking trouvé (pour calculer le prix)
    $parking = $this->createMock(Parking::class);
    $this->parkingRepo->method('findById')->willReturn($parking);

    // 4. Le parking dit que 60 minutes coûtent 500 cts (5€)
    $parking->expects($this->once())
      ->method('calculatePrice')
      ->with(60) // Le code doit calculer 60 min de retard
      ->willReturn(500);

    // ACT
    $response = $this->useCase->execute(new ExitParkingRequest('u1', 'p1'));

    // ASSERT
    // Coût attendu = 500 (Prix temps) + 2000 (Pénalité 20€) = 2500
    $this->assertEquals(2500, $response->extraCost);
    $this->assertEquals(60, $response->overstayMinutes);
    $this->assertTrue($response->penaltyApplied);

    // Vérifie que le prix est bien enregistré dans l'entité session
    $this->assertEquals(2500, $response->session->getPricePaid());
  }

  public function testExecuteWithSubscriptionActive(): void
  {
    // ARRANGE : Pas de réservation, mais un abonnement actif
    $now = new DateTimeImmutable();
    $session = new ParkingSession('sess-sub', 'p1', 'u1', null, $now->modify('-1 hour'));

    $this->sessionRepo->method('findActiveByUser')->willReturn($session);

    // On simule qu'un abonnement est trouvé pour "maintenant"
    $sub = $this->createMock(UserSubscription::class);
    $this->subscriptionRepo->expects($this->once())
      ->method('findActiveForUser')
      ->willReturn($sub);

    // ACT
    $response = $this->useCase->execute(new ExitParkingRequest('u1', 'p1'));

    // ASSERT
    $this->assertEquals(0, $response->extraCost); // Pas de surcoût
    $this->assertFalse($response->penaltyApplied);
  }

  public function testExecuteThrowsIfNoSession(): void
  {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Aucune session active");

    $this->sessionRepo->method('findActiveByUser')->willReturn(null);

    $this->useCase->execute(new ExitParkingRequest('u1', 'p1'));
  }

  public function testExecuteThrowsIfWrongParking(): void
  {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Aucune session active trouvée dans ce parking");

    // Session active mais dans le parking "p2", alors qu'on demande "p1"
    $session = new ParkingSession('s1', 'p2', 'u1', 'r1', new DateTimeImmutable());
    $this->sessionRepo->method('findActiveByUser')->willReturn($session);

    $this->useCase->execute(new ExitParkingRequest('u1', 'p1'));
  }
  // Test du cas où la réservation a disparu de la base (Incohérence de données)
  public function testExecuteWhenReservationIsMissingFromDb(): void
  {
    // ARRANGE
    $now = new DateTimeImmutable();
    $entryTime = $now->modify('-2 hours'); // Entré il y a 2h

    // La session dit "J'ai la réservation 'deleted-res'"
    $session = new ParkingSession('s1', 'p1', 'u1', 'deleted-res', $entryTime);

    $this->sessionRepo->method('findActiveByUser')->willReturn($session);

    // MAIS le repo ne la trouve pas (retourne null)
    $this->reservationRepo->expects($this->once())
      ->method('findById')
      ->with('deleted-res')
      ->willReturn(null);

    // Le parking est nécessaire pour le calcul du prix
    $parking = $this->createMock(Parking::class);
    $this->parkingRepo->method('findById')->willReturn($parking);

    // CONSÉQUENCE : On considère que tout le temps (120min) est du dépassement
    $parking->expects($this->once())
      ->method('calculatePrice')
      ->with(120)
      ->willReturn(1000); // 10€

    // ACT
    $response = $this->useCase->execute(new ExitParkingRequest('u1', 'p1'));

    // ASSERT
    $this->assertTrue($response->penaltyApplied);
    $this->assertEquals(120, $response->overstayMinutes);
    $this->assertEquals(3000, $response->extraCost); // 1000 + 2000 (Pénalité)
  }

  // Test du cas où l'utilisateur est entré (sans résa) et n'a plus d'abonnement valide
  public function testExecuteWhenNoReservationAndNoActiveSubscription(): void
  {
    // ARRANGE
    $now = new DateTimeImmutable();
    $entryTime = $now->modify('-60 minutes'); // Entré il y a 1h

    // Session SANS réservation ID (ex: entré avec un abo qui a expiré depuis)
    $session = new ParkingSession('s1', 'p1', 'u1', null, $entryTime);

    $this->sessionRepo->method('findActiveByUser')->willReturn($session);

    // Pas d'abonnement valide trouvé à l'instant T
    $this->subscriptionRepo->expects($this->once())
      ->method('findActiveForUser')
      ->willReturn(null);

    $parking = $this->createMock(Parking::class);
    $this->parkingRepo->method('findById')->willReturn($parking);

    // CONSÉQUENCE : Tout le temps (60min) est facturé + pénalité
    $parking->expects($this->once())
      ->method('calculatePrice')
      ->with(60)
      ->willReturn(500);

    // ACT
    $response = $this->useCase->execute(new ExitParkingRequest('u1', 'p1'));

    // ASSERT
    $this->assertTrue($response->penaltyApplied);
    $this->assertEquals(60, $response->overstayMinutes);
    $this->assertEquals(2500, $response->extraCost); // 500 + 2000
  }
  public function testExecuteThrowsExceptionIfParkingNotFoundDuringOverstayCalculation(): void
  {
    // ARRANGE
    $now = new DateTimeImmutable();

    // 1. Session active (entrée il y a 2h)
    $session = new ParkingSession('sess-1', 'p1', 'u1', 'res-1', $now->modify('-2 hours'));
    $this->sessionRepo->method('findActiveByUser')->willReturn($session);

    // 2. Réservation terminée il y a 1h (=> DÉPASSEMENT => On entre dans le if)
    $reservation = $this->createMock(Reservation::class);
    $reservation->method('getEndTime')->willReturn($now->modify('-1 hour'));
    $this->reservationRepo->method('findById')->willReturn($reservation);

    // 3. MAIS le repository Parking renvoie NULL (Parking introuvable/supprimé)
    $this->parkingRepo->expects($this->once())
      ->method('findById')
      ->with('p1')
      ->willReturn(null);

    // ASSERT
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Parking introuvable.");

    // ACT
    $this->useCase->execute(new ExitParkingRequest('u1', 'p1'));
  }
}
