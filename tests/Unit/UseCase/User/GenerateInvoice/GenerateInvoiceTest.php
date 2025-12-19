<?php

declare(strict_types=1);

namespace Tests\Unit\UseCase\User\GenerateInvoice;

use App\Domain\Entity\Account;
use App\Domain\Entity\Parking;
use App\Domain\Entity\Reservation;
use App\Domain\Repository\AccountRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\UseCase\User\GenerateInvoice\GenerateInvoiceRequest;
use App\UseCase\User\GenerateInvoice\GenerateInvoice;
use App\Domain\ValueObject\GpsCoordinates;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GenerateInvoiceTest extends TestCase
{
  private ReservationRepositoryInterface&MockObject $reservationRepo;
  private ParkingRepositoryInterface&MockObject $parkingRepo;
  private AccountRepositoryInterface&MockObject $accountRepo;
  private GenerateInvoice $useCase;

  protected function setUp(): void
  {
    // On mock les 3 repositories
    $this->reservationRepo = $this->createMock(ReservationRepositoryInterface::class);
    $this->parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
    $this->accountRepo = $this->createMock(AccountRepositoryInterface::class);

    $this->useCase = new GenerateInvoice(
      $this->reservationRepo,
      $this->parkingRepo,
      $this->accountRepo
    );
  }

  public function testExecuteGeneratesCorrectInvoiceDtoWhenEverythingIsOk(): void
  {
    // ARRANGE
    $resId = 'res-123456789'; // ID long
    $userId = 'user-1';
    $parkingId = 'park-1';
    $priceCents = 1200; // 12.00 € TTC -> Donc 10.00 € HT + 2.00 € TVA

    // 1. Mock Reservation
    $reservation = $this->createMock(Reservation::class);
    $reservation->method('getId')->willReturn($resId);
    $reservation->method('getUserId')->willReturn($userId);
    $reservation->method('getParkingId')->willReturn($parkingId);
    $reservation->method('getPricePaidInCents')->willReturn($priceCents);
    $reservation->method('getStartTime')->willReturn(new \DateTimeImmutable('2025-01-01 10:00'));
    $reservation->method('getEndTime')->willReturn(new \DateTimeImmutable('2025-01-01 12:00'));
    // Si tu as gardé le check de statut dans ton code final, décommente la ligne ci-dessous :
    // $reservation->method('getStatus')->willReturn(Reservation::STATUS_CONFIRMED);

    $this->reservationRepo->expects($this->once())
      ->method('findById')
      ->with($resId)
      ->willReturn($reservation);

    // 2. Mock User (Account)
    $user = $this->createMock(Account::class);
    $user->method('getEmail')->willReturn('client@test.com');

    $this->accountRepo->expects($this->once())
      ->method('findById')
      ->with($userId)
      ->willReturn($user);

    // 3. Mock Parking & Coordinates
    // Attention : ton code utilise getCoordinates()->getLatitude()
    // Il faut mocker cette chaîne d'appel
    $coordinates = $this->createMock(GpsCoordinates::class); // Ou ta classe de ValueObject
    $coordinates->method('getLatitude')->willReturn(48.8566);
    $coordinates->method('getLongitude')->willReturn(2.3522);

    $parking = $this->createMock(Parking::class);
    $parking->method('getName')->willReturn('Super Parking');
    $parking->method('getCoordinates')->willReturn($coordinates);

    $this->parkingRepo->expects($this->once())
      ->method('findById')
      ->with($parkingId)
      ->willReturn($parking);

    $request = new GenerateInvoiceRequest($resId, $userId);

    // ACT
    $response = $this->useCase->execute($request);
    $dto = $response->invoice;

    // ASSERT
    // Vérification du numéro de facture (INV- + 8 premiers chars)
    $this->assertEquals('INV-RES-1234', $dto->invoiceNumber);

    // Vérification des infos client/vendeur
    $this->assertEquals('client@test.com', $dto->buyerEmail);
    $this->assertEquals('Super Parking', $dto->sellerName);
    $this->assertStringContainsString('48.8566', $dto->sellerAddress);

    // Vérification des calculs financiers
    $this->assertEquals(12.00, $dto->totalAmount, 'Le total TTC doit être de 12.00');
    $this->assertEquals(10.00, $dto->amountExclTax, 'Le HT doit être de 10.00');
    $this->assertEquals(2.00, $dto->vatAmount, 'La TVA doit être de 2.00');
  }

  public function testExecuteThrowsExceptionIfReservationNotFound(): void
  {
    // ARRANGE
    $this->reservationRepo->method('findById')->willReturn(null);

    // ASSERT
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Réservation introuvable");

    // ACT
    $this->useCase->execute(new GenerateInvoiceRequest('bad-id', 'u1'));
  }

  public function testExecuteThrowsExceptionIfUserDoesNotOwnReservation(): void
  {
    // ARRANGE
    $reservation = $this->createMock(Reservation::class);
    $reservation->method('getUserId')->willReturn('owner-user');

    $this->reservationRepo->method('findById')->willReturn($reservation);

    // ASSERT
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Accès interdit");

    // ACT
    // On essaie d'accéder avec 'hacker-user' alors que la résa est à 'owner-user'
    $this->useCase->execute(new GenerateInvoiceRequest('r1', 'hacker-user'));
  }

  public function testExecuteHandlesMissingParkingGracefully(): void
  {
    // ARRANGE
    // Cas où le parking a été supprimé de la BDD entre temps
    $reservation = $this->createMock(Reservation::class);
    $reservation->method('getUserId')->willReturn('u1');
    $reservation->method('getId')->willReturn('res-001');
    $reservation->method('getPricePaidInCents')->willReturn(100);
    $reservation->method('getStartTime')->willReturn(new \DateTimeImmutable());
    $reservation->method('getEndTime')->willReturn(new \DateTimeImmutable());
    $reservation->method('getParkingId')->willReturn('deleted-parking');

    $this->reservationRepo->method('findById')->willReturn($reservation);

    // Mock User
    $user = $this->createMock(Account::class);
    $user->method('getEmail')->willReturn('a@a.com');
    $this->accountRepo->method('findById')->willReturn($user);

    // Mock Parking NULL
    $this->parkingRepo->method('findById')->with('deleted-parking')->willReturn(null);

    // ACT
    $response = $this->useCase->execute(new GenerateInvoiceRequest('res-001', 'u1'));

    // ASSERT
    $this->assertEquals('Parking Inconnu', $response->invoice->sellerName);
    $this->assertEquals('Coordonnées GPS: ', $response->invoice->sellerAddress);
  }
}
