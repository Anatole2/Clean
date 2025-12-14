<?php

namespace Tests\Application\UseCase;

use App\Application\UseCase\SubscribeToParking;
use App\Domain\Entity\Parking;
use App\Domain\Entity\User;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\SubscriptionRepositoryInterface;
use App\Domain\Service\AvailabilityCheckerInterface;
use PHPUnit\Framework\TestCase;

class SubscribeToParkingTest extends TestCase
{
    private $parkingRepoMock;
    private $subscriptionRepoMock;
    private $availabilityCheckerMock;
    private $userMock;
    private $parkingMock;
    
    protected function setUp(): void
    {
        // 1. Initialisation des Mocks des interfaces
        $this->parkingRepoMock = $this->createMock(ParkingRepositoryInterface::class);
        $this->subscriptionRepoMock = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->availabilityCheckerMock = $this->createMock(AvailabilityCheckerInterface::class);
        
        // 2. Initialisation des Mocks des Entités
        $this->userMock = $this->createMock(User::class);
        $this->parkingMock = $this->createMock(Parking::class);

        // Configuration minimale du Parking : on suppose qu'il a une méthode pour le prix
        $this->parkingMock->method('calculateSubscriptionPrice')->willReturn(200.00); // Prix mensuel à peu près
    }
    
    public function testExecuteSavesSubscriptionOnSuccess(): void
    {
        // Mise en place des données
        $parkingId = 42;
        $dateDebut = new \DateTimeImmutable('2026-01-01');
        $dateFin = new \DateTimeImmutable('2026-02-01');
        $creneaux = [
            'Lundi' => ['08:00', '18:00'],
            'Mardi' => ['08:00', '18:00']
        ];
        
        // 1. Définir le comportement des Repositories
        
        // Simuler la récupération du Parking
        $this->parkingRepoMock
            ->expects($this->once())
            ->method('findById')
            ->with($parkingId)
            ->willReturn($this->parkingMock);
            
        // Simuler la vérification de disponibilité (Doit être disponible)
        // NOTE: On suppose que la méthode d'AvailabilityChecker est `checkSubscriptionAvailability`
        $this->availabilityCheckerMock
            ->expects($this->once())
            ->method('checkSubscriptionAvailability')
            ->with($this->parkingMock, $creneaux, $dateDebut, $dateFin)
            ->willReturn(true);
            
        // 2. Définir l'attente principale : la méthode save doit être appelée UNE FOIS.
        $this->subscriptionRepoMock
            ->expects($this->once())
            ->method('save');
            
        $useCase = new SubscribeToParking(
            $this->parkingRepoMock, 
            $this->subscriptionRepoMock, 
            $this->availabilityCheckerMock
        );
        
        // ACT (Exécution)
        $useCase->execute($this->userMock, $parkingId, $dateDebut, $dateFin, $creneaux);
        
        // ASSERT (Vérification)
       
        $this->assertTrue(true); 
    }
    public function testExecuteThrowsExceptionIfNotAvailable(): void
    {
        // ARRANGE
        $parkingId = 42;
        $dateDebut = new \DateTimeImmutable('2026-01-01');
        $dateFin = new \DateTimeImmutable('2026-02-01');
        $creneaux = ['Lundi' => ['08:00', '18:00']];
        
        // Simuler la récupération du Parking
        $this->parkingRepoMock
            ->method('findById')
            ->willReturn($this->parkingMock);
            
        // Simuler la vérification de disponibilité (Doit être INDISPONIBLE)
        $this->availabilityCheckerMock
            ->expects($this->once())
            ->method('checkSubscriptionAvailability')
            ->willReturn(false); // Indisponible
            
        // Définir l'attente principale : la méthode save ne doit JAMAIS être appelée.
        $this->subscriptionRepoMock
            ->expects($this->never())
            ->method('save');
            
        $useCase = new SubscribeToParking(
            $this->parkingRepoMock, 
            $this->subscriptionRepoMock, 
            $this->availabilityCheckerMock
        );
        
        // ATTENTION : On doit s'attendre à l'exception que le Use Case est censé lever
        $this->expectException(\App\Domain\Exception\SubscriptionNotAvailableException::class);
        
        // ACT
        $useCase->execute($this->userMock, $parkingId, $dateDebut, $dateFin, $creneaux);
    }
}