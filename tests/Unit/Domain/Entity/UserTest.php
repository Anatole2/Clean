<?php

namespace Tests\Unit\Domain\Entity;

use PHPUnit\Framework\TestCase;
use App\Domain\Entity\User;

class UserTest extends TestCase
{
    public function testUserEntityStoresBaseDataCorrectly(): void
    {
        // 1. Préparation des données
        $id = '1';
        $email = 'john.doe@driver.com';
        $password = 'password123';
        $firstName = 'John';
        $lastName = 'Doe';

        // 2. Création de l'Entité
        $user = new User($id, $email, $password, $firstName, $lastName);

        // 3. Vérification de l'état de base (hérité de Account)
        $this->assertEquals($id, $user->getId());
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($firstName, $user->getFirstName());
        $this->assertEquals($lastName, $user->getLastName());

        // 4. Vérification de la fonction d'authentification
        $this->assertTrue($user->verifyPassword($password), "Le mot de passe doit être vérifié correctement.");
        $this->assertFalse($user->verifyPassword('wrongpassword'), "Un mot de passe incorrect doit échouer à la vérification.");
    }

    public function testUserEntityReturnsCorrectRole(): void
    {
        $user = new User('2', 'a@b.com', 'p', 'A', 'B');
        
        $this->assertEquals('DRIVER', $user->getRole());
    }

    public function testUserEntityManagesReservationsAndStationnementsCorrectly(): void
    {
        $user = new User('3', 'user@test.com', 'p', 'Test', 'User');
        
        $reservation1 = $this->createMock(Reservation::class);
        $reservation2 = $this->createMock(Reservation::class);
        $stationnement1 = $this->createMock(Stationnement::class);

        $this->assertEmpty($user->getReservations());
        $this->assertEmpty($user->getStationnements());
        
        $user->addReservation($reservation1);
        $user->addReservation($reservation2);
        $user->addStationnement($stationnement1);

        // Vérification des Réservations
        $this->assertCount(2, $user->getReservations(), "La liste de réservations doit contenir 2 éléments.");
        $this->assertSame($reservation1, $user->getReservations()[0], "Le premier élément ajouté doit être correct.");
        $this->assertSame($reservation2, $user->getReservations()[1], "Le second élément ajouté doit être correct.");

        // Vérification des Stationnements
        $this->assertCount(1, $user->getStationnements(), "La liste de stationnements doit contenir 1 élément.");
        $this->assertSame($stationnement1, $user->getStationnements()[0], "Le stationnement ajouté doit être correct.");
    }
}