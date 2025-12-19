<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\User\GetReservations;

use App\Domain\Entity\Reservation;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\UseCase\User\GetReservations\GetReservationsRequest;
use App\UseCase\User\GetReservations\GetReservations;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetReservationsTest extends TestCase
{
  private ReservationRepositoryInterface&MockObject $repository;
  private GetReservations $useCase;

  protected function setUp(): void
  {
    $this->repository = $this->createMock(ReservationRepositoryInterface::class);
    $this->useCase = new GetReservations($this->repository);
  }

  public function testExecuteReturnsMappedSummaries(): void
  {
    // ARRANGE
    // On crée des dates dynamiques pour être sûr des tests Passé/Futur
    $pastStart = new DateTimeImmutable('-5 hours');
    $pastEnd = new DateTimeImmutable('-4 hours'); // Terminée

    $futureStart = new DateTimeImmutable('+1 hour');
    $futureEnd = new DateTimeImmutable('+2 hours'); // Pas encore commencée

    // Réservation 1 : Terminée et Confirmée => Facture OUI
    $res1 = new Reservation(
      'res-1',
      'u1',
      'p1',
      $pastStart,
      $pastEnd,
      1000,
      Reservation::STATUS_CONFIRMED
    );

    // Réservation 2 : Future et Confirmée => Facture NON (pas encore passée)
    $res2 = new Reservation(
      'res-2',
      'u1',
      'p1',
      $futureStart,
      $futureEnd,
      2000,
      Reservation::STATUS_CONFIRMED
    );

    // Réservation 3 : Passée mais Annulée => Facture NON
    $res3 = new Reservation(
      'res-3',
      'u1',
      'p1',
      $pastStart,
      $pastEnd,
      0,
      Reservation::STATUS_CANCELLED
    );

    // Mock du repository
    $this->repository->expects($this->once())
      ->method('findByUserId')
      ->with('u1')
      ->willReturn([$res1, $res2, $res3]);

    $request = new GetReservationsRequest('u1');

    // ACT
    $response = $this->useCase->execute($request);

    // ASSERT
    $this->assertCount(3, $response->reservations);

    // Vérification Réservation 1 (OK)
    $dto1 = $response->reservations[0];
    $this->assertEquals('res-1', $dto1->id);
    $this->assertTrue($dto1->canGenerateInvoice, "Devrait pouvoir générer facture si terminée et confirmée");

    // Vérification Réservation 2 (Trop tôt)
    $dto2 = $response->reservations[1];
    $this->assertEquals('res-2', $dto2->id);
    $this->assertFalse($dto2->canGenerateInvoice, "Ne devrait pas générer facture si dans le futur");

    // Vérification Réservation 3 (Annulée)
    $dto3 = $response->reservations[2];
    $this->assertEquals('res-3', $dto3->id);
    $this->assertFalse($dto3->canGenerateInvoice, "Ne devrait pas générer facture si annulée");
  }

  public function testExecuteReturnsEmptyListWhenNoReservations(): void
  {
    $this->repository->expects($this->once())
      ->method('findByUserId')
      ->with('u1')
      ->willReturn([]);

    $response = $this->useCase->execute(new GetReservationsRequest('u1'));

    $this->assertEmpty($response->reservations);
    $this->assertIsArray($response->reservations);
  }
}
