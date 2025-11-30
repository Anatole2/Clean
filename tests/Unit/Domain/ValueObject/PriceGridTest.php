<?php

namespace Tests\Unit\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use App\Domain\ValueObject\PriceGrid;
use InvalidArgumentException;

class PriceGridTest extends TestCase
{
  private array $exampleRates = [
    30  => 60,   // 30 min = 0.60€
    60  => 120,  // 1h     = 1.20€
    120 => 320,  // 2h     = 3.20€
    1440 => 1000 // 24h    = 10.00€
  ];

  public function testCalculatesExactThreshold(): void
  {
    $grid = new PriceGrid($this->exampleRates);

    // Si je reste pile 30 minutes, je paie le tarif 30 min
    $this->assertEquals(60, $grid->calculatePrice(30));
  }

  public function testCalculatesNextThresholdIfExceeded(): void
  {
    $grid = new PriceGrid($this->exampleRates);

    // Si je reste 31 minutes, j'ai entamé la tranche d'1h -> je paie 1h (120)
    // "Toute tranche horaire entamée est due"
    $this->assertEquals(120, $grid->calculatePrice(31));

    // Si je reste 1h01, je passe au tarif 2h
    $this->assertEquals(320, $grid->calculatePrice(61));
  }

  public function testReturnsMaxPriceIfDurationExceedsGrid(): void
  {
    $grid = new PriceGrid($this->exampleRates);

    // Si je reste 25h (1500 min), je paie le max (10.00€)
    $this->assertEquals(1000, $grid->calculatePrice(1500));
  }

  public function testHandlesUnsortedInput(): void
  {
    // On donne les tarifs dans le désordre
    $messyRates = [
      120 => 320,
      30  => 60,
      60  => 120
    ];

    $grid = new PriceGrid($messyRates);

    // Le système doit avoir trié et comprendre que 45min est < 60min
    $this->assertEquals(120, $grid->calculatePrice(45));
  }

  public function testCannotCreateEmptyGrid(): void
  {
    $this->expectException(InvalidArgumentException::class);
    new PriceGrid([]);
  }

  public function testFreeForZeroMinutes(): void
  {
    $grid = new PriceGrid($this->exampleRates);
    $this->assertEquals(0, $grid->calculatePrice(0));
  }
  public function testToArrayReturnsSortedRates(): void
  {
    // On injecte dans le désordre (1h avant 30min)
    $messyRates = [
      60 => 120,
      30 => 60
    ];

    // On s'attend à ce que ce soit trié à la sortie
    $expectedSortedRates = [
      30 => 60,
      60 => 120
    ];

    $grid = new PriceGrid($messyRates);

    // On vérifie que toArray() retourne bien le tableau attendu
    $this->assertSame($expectedSortedRates, $grid->toArray());
  }
}
