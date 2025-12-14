<?php

namespace Tests\UseCase;

use PHPUnit\Framework\TestCase;
use App\UseCase\EnterParking;
use App\Domain\Entity\User;
use App\Domain\Entity\Parking;
use App\Domain\Entity\Reservation;
use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Repository\ParkingRepositoryInterface;
use DateTimeImmutable;

class EnterParkingTest extends TestCase
{
    public function testEntryRefusedWithoutReservation()
    {
        
        $stationnementRepo = $this->createMock(StationnementRepositoryInterface::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
        
       
        $userRepo->method('findById')->willReturn(new User());
        $parkingRepo->method('findById')->willReturn(new Parking());

     
        $reservationRepo = $this->createMock(ReservationRepositoryInterface::class);
        $reservationRepo->method('findActiveForUser')->willReturn(null);

        $useCase = new EnterParking($stationnementRepo, $reservationRepo, $userRepo, $parkingRepo);

      
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Accès refusé");

        $useCase->execute("user-1", "parking-1");
    }

    public function testEntryAcceptedWithReservation()
    {
        
        $stationnementRepo = $this->createMock(StationnementRepositoryInterface::class);
        $userRepo = $this->createMock(UserRepositoryInterface::class);
        $parkingRepo = $this->createMock(ParkingRepositoryInterface::class);
        $reservationRepo = $this->createMock(ReservationRepositoryInterface::class);
        
        $userRepo->method('findById')->willReturn(new User());
        $parkingRepo->method('findById')->willReturn(new Parking());

        
        $futureEnd = new DateTimeImmutable('+2 hours');
        $reservation = new Reservation($futureEnd, 123); 
        $reservationRepo->method('findActiveForUser')->willReturn($reservation);

       
        $stationnementRepo->method('findActiveForUser')->willReturn(null);

       
        $stationnementRepo->expects($this->once())->method('save');

        $useCase = new EnterParking($stationnementRepo, $reservationRepo, $userRepo, $parkingRepo);

        $result = $useCase->execute("user-1", "parking-1");

        $this->assertEquals('opened', $result['status']);
        $this->assertEquals('Bienvenue ! Barrière ouverte.', $result['message']);
    }
}

