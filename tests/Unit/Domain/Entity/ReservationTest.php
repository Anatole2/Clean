<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Reservation;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ReservationTest extends TestCase
{
  public function testItCanBeCreatedWithValidDataAndGettersWork(): void
  {
    // ARRANGE
    $start = new DateTimeImmutable('2024-01-01 10:00');
    $end = new DateTimeImmutable('2024-01-01 12:00');

    // ACT
    $reservation = new Reservation('id-1', 'user-1', 'parking-1', $start, $end, 500);

    // ASSERT - On appelle TOUS les getters pour le coverage
    $this->assertEquals('id-1', $reservation->getId());
    $this->assertEquals('user-1', $reservation->getUserId());     // Nouveau
    $this->assertEquals('parking-1', $reservation->getParkingId()); // Nouveau
    $this->assertSame($start, $reservation->getStartTime());      // Nouveau
    $this->assertSame($end, $reservation->getEndTime());          // Nouveau
    $this->assertEquals(500, $reservation->getPricePaidInCents());
    $this->assertEquals(Reservation::STATUS_CONFIRMED, $reservation->getStatus());
  }

  public function testItCanBeCancelled(): void
  {
    // ARRANGE
    $start = new DateTimeImmutable('2024-01-01 10:00');
    $end = new DateTimeImmutable('2024-01-01 12:00');
    $reservation = new Reservation('id-1', 'user-1', 'parking-1', $start, $end, 500);

    // ACT
    $reservation->cancel();

    // ASSERT
    $this->assertEquals(Reservation::STATUS_CANCELLED, $reservation->getStatus());
  }

  public function testItThrowsExceptionIfEndBeforeStart(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("La date de fin doit être postérieure à la date de début.");

    $start = new DateTimeImmutable('2024-01-01 12:00');
    $end = new DateTimeImmutable('2024-01-01 10:00');

    new Reservation('id', 'u', 'p', $start, $end, 100);
  }

  public function testItThrowsExceptionIfPriceIsNegative(): void
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("Le prix ne peut pas être négatif.");

    $start = new DateTimeImmutable('2024-01-01 10:00');
    $end = new DateTimeImmutable('2024-01-01 11:00');

    new Reservation('id', 'u', 'p', $start, $end, -50);
  }
}
