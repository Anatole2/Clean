<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\ParkingSession;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ParkingSessionTest extends TestCase
{
  public function testItCanBeCreatedAndIsOngoing(): void
  {
    $entry = new DateTimeImmutable();
    $session = new ParkingSession(
      'sess-1',
      'p-1',
      'u-1',
      'res-1',
      $entry
    );

    $this->assertTrue($session->isOngoing());
    $this->assertNull($session->getExitTime());
    $this->assertEquals(0, $session->getPricePaid());
  }

  public function testItCanBeClosed(): void
  {
    $entry = new DateTimeImmutable('-2 hours');
    $session = new ParkingSession(
      'sess-1',
      'p-1',
      'u-1',
      'res-1',
      $entry
    );

    $exit = new DateTimeImmutable();
    $price = 1500; // 15.00€

    // Action : Fermeture
    $session->close($exit, $price);

    $this->assertFalse($session->isOngoing());
    $this->assertEquals($exit, $session->getExitTime());
    $this->assertEquals($price, $session->getPricePaid());
  }

  public function testCloseThrowsExceptionIfExitBeforeEntry(): void
  {
    $this->expectException(\InvalidArgumentException::class);

    $entry = new DateTimeImmutable('12:00');
    $session = new ParkingSession('s1', 'p1', 'u1', 'r1', $entry);

    // Erreur : Sortie à 11:00 alors qu'entré à 12:00
    $session->close(new DateTimeImmutable('11:00'), 100);
  }
}
