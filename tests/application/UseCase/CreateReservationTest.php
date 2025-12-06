<?php

namespace Tests\Application\UseCase;

use App\Application\UseCase\CreateReservation;
use App\Domain\Entity\Parking;
use App\Domain\Entity\User;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Service\AvailabilityCheckerInterface;
use PHPUnit\Framework\TestCase;

class CreateReservationTest extends TestCase
{
    private $parkingRepoMock;
    private $reservationRepoMock;
    private $availabilityCheckerMock;
    private $userMock;
    private $parkingMock;
    
    protected function setUp(): void
    {
        // Initialisation de tous les Mocks pour ne pas les répéter dans chaque test
        $this->parkingRepoMock = $this->createMock(ParkingRepositoryInterface::class);
        $this->reservationRepoMock = $this->createMock(ReservationRepositoryInterface::class);
        $this->availabilityCheckerMock = $this->createMock(AvailabilityCheckerInterface::class);
        
        // Mocks pour les Entités (nécessaires pour les tests)
        $this->userMock = $this->createMock(User::class);
        $this->parkingMock = $this->createMock(Parking::class);

        // Configuration du ParkingMock pour les propriétés nécessaires au calcul du prix
        // Ex: Assume que le Parking a une méthode pour récupérer le tarif, même si la logique est ailleurs
        $this->parkingMock->method('getTarifHoraire')->willReturn(10.0);
    }
    
    public function testExecuteSavesReservationOnSuccess(): void
    {
        // ARRANGE (Mise en place)
        $userId = 1;
        $parkingId = 42;
        $debut = new \DateTimeImmutable('2025-12-05 10:00:00');
        $fin = new \DateTimeImmutable('2025-12-05 12:00:00'); // Durée : 2h
        
        // 1. Définir le comportement des Repositories et Services
        
        // Simuler la récupération du Parking
        $this->parkingRepoMock
            ->method('findById')
            ->with($parkingId)
            ->willReturn($this->parkingMock);
            
        // Simuler la vérification de disponibilité (Doit être disponible)
        $this->availabilityCheckerMock
            ->method('isAvailable')
            ->with($this->parkingMock, $debut, $fin)
            ->willReturn(true);
            
        // 2. Définir l'attente principale : la méthode save doit être appelée UNE FOIS.
        $this->reservationRepoMock
            ->expects($this->once())
            ->method('save');
            // On pourrait faire des assertions sur l'objet Reservation passé à save()
            
        $useCase = new CreateReservation(
            $this->parkingRepoMock, 
            $this->reservationRepoMock, 
            $this->availabilityCheckerMock
        );
        
        // ACT (Exécution)
        // NOTE: On suppose que le UseCase peut récupérer l'entité User (ici $this->userMock)
        // ou que l'entité User est passée en argument pour simplification du test.
        // Pour les besoins du test, simplifions l'appel
        
        // Simuler la récupération de l'User
        // (En réalité, il faudrait un UserRepository, mais simplifions pour le test du flux principal)
        $useCase->execute($this->userMock, $parkingId, $debut, $fin);
        
        // ASSERT (Vérification)
        // La vérification de save() a déjà été faite dans $this->reservationRepoMock->expects($this->once()).
        $this->assertTrue(true); // Placeholder pour s'assurer que le test a fonctionné sans erreur
    }
    public function testExecuteThrowsExceptionIfParkingIsNotAvailable(): void
    {
        // ARRANGE
        $parkingId = 42;
        $debut = new \DateTimeImmutable('2025-12-05 10:00:00');
        $fin = new \DateTimeImmutable('2025-12-05 12:00:00');
        
        // Simuler la récupération du Parking
        $this->parkingRepoMock
            ->method('findById')
            ->willReturn($this->parkingMock);
            
        // Simuler la vérification de disponibilité (Doit être INDISPONIBLE)
        $this->availabilityCheckerMock
            ->method('isAvailable')
            ->willReturn(false);
            
        // Définir l'attente principale : la méthode save ne doit JAMAIS être appelée.
        $this->reservationRepoMock
            ->expects($this->never())
            ->method('save');
            
        $useCase = new CreateReservation(
            $this->parkingRepoMock, 
            $this->reservationRepoMock, 
            $this->availabilityCheckerMock
        );
        
        // ATTENTION : Pour ce test, il faut que le UseCase lève une exception spécifique
        // si la place n'est pas disponible. Créons une exception fictive.
        $this->expectException(\App\Domain\Exception\ParkingNotAvailableException::class);
        
        // ACT
        $useCase->execute($this->userMock, $parkingId, $debut, $fin);
    }

}