<?php

namespace Tests\Unit\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use App\Domain\ValueObject\GpsCoordinates;
use InvalidArgumentException;

class GpsCoordinatesTest extends TestCase
{
  public function testCanCreateValidCoordinates(): void
  {
    // Coordonnées de Paris
    $lat = 48.8566;
    $lon = 2.3522;

    $gps = new GpsCoordinates($lat, $lon);

    $this->assertEquals($lat, $gps->getLatitude());
    $this->assertEquals($lon, $gps->getLongitude());
    $this->assertStringContainsString('48.8566', (string)$gps);
  }

  public function testCannotCreateLatitudeTooLow(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("La latitude doit être comprise entre -90 et 90 degrés");

    new GpsCoordinates(-91.0, 0.0);
  }

  public function testCannotCreateLatitudeTooHigh(): void
  {
    $this->expectException(InvalidArgumentException::class);

    new GpsCoordinates(90.0001, 0.0);
  }

  public function testCannotCreateLongitudeTooLow(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("La longitude doit être comprise entre -180 et 180 degrés");

    new GpsCoordinates(0.0, -181.0);
  }

  public function testCannotCreateLongitudeTooHigh(): void
  {
    $this->expectException(InvalidArgumentException::class);

    new GpsCoordinates(0.0, 180.1);
  }
}
