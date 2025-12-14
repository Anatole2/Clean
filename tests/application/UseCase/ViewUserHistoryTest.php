<?php

namespace Tests\Application\UseCase;

use App\Application\UseCase\ViewUserHistory;
use App\Domain\Entity\User;
use App\Domain\Entity\Reservation;
use App\Domain\Entity\Abonnement;
use App\Domain\Entity\Stationnement;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\SubscriptionRepositoryInterface;
use App\Domain\Repository\StationnementRepositoryInterface;
use PHPUnit\Framework\TestCase;

class ViewUserHistoryTest extends TestCase
{
    private $reservationRepoMock;
    private $subscriptionRepoMock;
    private $stationnementRepoMock;
    private $userMock;
    
    protected function setUp(): void
    {
        // Initialisation des Mocks des interfaces Repository
        $this->reservationRepoMock = $this->createMock(ReservationRepositoryInterface::class);
        $this->subscriptionRepoMock = $this->createMock(SubscriptionRepositoryInterface::class);
        $this->stationnementRepoMock = $this->createMock(StationnementRepositoryInterface::class);
        
        // Mock de l'entité User (l'argument passé au Use Case)
        $this->userMock = $this->createMock(User::class);
    }
    
    public function testExecuteAssemblesHistoryFromAllRepositories(): void
    {
        // ARRANGE (Mise en place des données simulées)
        
        // Simuler les données retournées par chaque Repository
        $mockReservations = [$this->createMock(Reservation::class)];
        $mockSubscriptions = [$this->createMock(Abonnement::class), $this->createMock(Abonnement::class)];
        $mockStationnements = [$this->createMock(Stationnement::class)];

        // 1. Définir le comportement attendu sur ReservationRepository
        $this->reservationRepoMock
            ->expects($this->once())
            ->method('findByUser')
            ->with($this->userMock)
            ->willReturn($mockReservations);
            
        // 2. Définir le comportement attendu sur SubscriptionRepository
        $this->subscriptionRepoMock
            ->expects($this->once())
            ->method('findActiveByUser')
            ->with($this->userMock)
            ->willReturn($mockSubscriptions);
            
        // 3. Définir le comportement attendu sur StationnementRepository
        $this->stationnementRepoMock
            ->expects($this->once())
            ->method('findCompletedByUser')
            ->with($this->userMock)
            ->willReturn($mockStationnements);
            
        // 4. Création du Use Case
        $useCase = new ViewUserHistory(
            $this->reservationRepoMock, 
            $this->subscriptionRepoMock, 
            $this->stationnementRepoMock
        );
        
        // ACT (Exécution)
        $result = $useCase->execute($this->userMock);
        
        // ASSERT (Vérification)
        
        // 1. Vérifier que le résultat est un tableau
        $this->assertIsArray($result);
        
        // 2. Vérifier que le tableau contient les trois clés attendues
        $this->assertArrayHasKey('reservations', $result);
        $this->assertArrayHasKey('subscriptions', $result);
       $this->assertArrayHasKey('stationnements', $result);
        
        // 3. Vérifier que chaque clé contient bien les données simulées (même nombre d'éléments et même contenu)
        $this->assertSame($mockReservations, $result['reservations'], 'Le tableau de réservations est incorrect.');
        $this->assertSame($mockSubscriptions, $result['subscriptions'], 'Le tableau d\'abonnements est incorrect.');
       $this->assertSame($mockStationnements, $result['stationnements'], 'Le tableau de stationnements est incorrect.');
    }
    
    public function testExecuteHandlesEmptyHistory(): void
    {
        // ARRANGE (Simuler que tous les Repositories retournent des tableaux vides)
        $this->reservationRepoMock->method('findByUser')->willReturn([]);
        $this->subscriptionRepoMock->method('findActiveByUser')->willReturn([]);
        $this->stationnementRepoMock->method('findCompletedByUser')->willReturn([]);
        
        $useCase = new ViewUserHistory(
            $this->reservationRepoMock, 
            $this->subscriptionRepoMock, 
            $this->stationnementRepoMock
        );
        
        // ACT
        $result = $useCase->execute($this->userMock);
        
        // ASSERT
        $this->assertEmpty($result['reservations']);
        $this->assertEmpty($result['subscriptions']);
        $this->assertEmpty($result['parkings']);
    }
}