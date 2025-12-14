<?php

namespace Tests\Domain\Service;

use PHPUnit\Framework\TestCase;
use App\Domain\Service\AvailabilityCalculator;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\SubscriptionRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use DateTimeImmutable;

class AvailabilityCalculatorTest extends TestCase
{
    public function testIsFull()
    {
    
        $parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
        $reservationRepo = $this->createMock(ReservationRepositoryInterface::class);
        $subRepo = $this->createMock(SubscriptionRepositoryInterface::class);
        $stationnementRepo = $this->createMock(StationnementRepositoryInterface::class);

       
        $parkingRepo->method('getTotalSpots')->willReturn(10);

     
        $reservationRepo->method('countOverlapping')->willReturn(8);
        
       
        $subRepo->method('countActiveForParking')->willReturn(2);

      
        $stationnementRepo->method('countActiveOverlapping')->willReturn(0);
        
        $service = new AvailabilityCalculator($parkingRepo, $reservationRepo, $subRepo, $stationnementRepo);
        
        $start = new DateTimeImmutable('2025-01-01 10:00');
        $end = new DateTimeImmutable('2025-01-01 11:00');

        
        $this->assertFalse($service->isSpotAvailable('p1', $start, $end));
    }

    public function testHasSpace()
    {
     
        $parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
        $reservationRepo = $this->createMock(ReservationRepositoryInterface::class);
        $subRepo = $this->createMock(SubscriptionRepositoryInterface::class);
        $stationnementRepo = $this->createMock(StationnementRepositoryInterface::class);

      
        $parkingRepo->method('getTotalSpots')->willReturn(10);
        
  
        $reservationRepo->method('countOverlapping')->willReturn(5);
        $subRepo->method('countActiveForParking')->willReturn(1);
        $stationnementRepo->method('countActiveOverlapping')->willReturn(0);

        $service = new AvailabilityCalculator($parkingRepo, $reservationRepo, $subRepo, $stationnementRepo);
        
     
        $this->assertTrue($service->isSpotAvailable('p1', new DateTimeImmutable(), new DateTimeImmutable()));
    }
}

