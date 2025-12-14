<?php

namespace Tests\UseCase;

use PHPUnit\Framework\TestCase;
use App\UseCase\ExitParking;
use App\Domain\Service\PriceCalculator;
use App\Domain\Entity\Stationnement;
use App\Domain\Entity\Parking;
use App\Domain\Entity\Reservation;
use App\Domain\Entity\User;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use DateTimeImmutable;

class ExitParkingTest extends TestCase
{
    public function testExitWithPenalty()
    {
       
        $userId = "user-123";
        $parkingId = "parking-abc";
        $reservationId = 999;
        
      
        $finReservation = new DateTimeImmutable('2025-01-01 14:00:00');
        $reservation = new Reservation($finReservation);

       
        $debutStationnement = new DateTimeImmutable('2025-01-01 12:00:00');
        
      
        $parking = new Parking(); 
        $user = $this->createMock(User::class);
        
       
        $stationnement = new Stationnement(
            $user,
            $parking,
            $debutStationnement,
            null, 
            1,   
            $reservationId
        );

       
        $stationnementRepo = $this->createMock(StationnementRepositoryInterface::class);
        $stationnementRepo->method('findActiveForUser')
            ->willReturn($stationnement);

    
        $stationnementRepo->expects($this->once())
            ->method('save');

       
        $parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
        $parkingRepo->method('findById')->willReturn($parking);

        
        $reservationRepo = $this->createMock(ReservationRepositoryInterface::class);
        $reservationRepo->method('findById')->willReturn($reservation);

      
        $useCase = new ExitParking(
            $stationnementRepo,
            $parkingRepo,
            $reservationRepo,
            new PriceCalculator()
        );

    
        $now = new DateTimeImmutable();
        $finReservationPasse = $now->modify('-1 hour'); 
        
       
        $reservationRepo->method('findById')->willReturn(new Reservation($finReservationPasse));

    
        $result = $useCase->execute($userId, $parkingId);

       
        $this->assertEquals('closed', $result['status']);
        
 
        $this->assertGreaterThan(20.0, $result['total_paid']);
     
        $this->assertStringContainsString('Sortie validée', $result['message']);
    }
}

