<?php

namespace Tests\Unit\Domain\Entity;

use PHPUnit\Framework\TestCase;
use App\Domain\Entity\Owner;

class OwnerTest extends TestCase
{
    public function testOwnerEntityStoresBaseDataCorrectly(): void
    {
        // 1. Préparation des données
        $id = '1';
        $email = 'jane.smith@owner.com';
        $password = 'securepass';
        $firstName = 'Jane';
        $lastName = 'Smith';

        // 2. Création de l'Entité
        $owner = new Owner($id, $email, $password, $firstName, $lastName);

        // 3. Vérification de l'état de base (hérité de Account)
        $this->assertEquals($id, $owner->getId());
        $this->assertEquals($email, $owner->getEmail());
        $this->assertEquals($firstName, $owner->getFirstName());
        $this->assertEquals($lastName, $owner->getLastName());

        // 4. Vérification de la fonction d'authentification
        $this->assertTrue($user->verifyPassword($password), "Le mot de passe doit être vérifié correctement.");
        $this->assertFalse($user->verifyPassword('wrongpassword'), "Un mot de passe incorrect doit échouer à la vérification.");
    }

    public function testOwnerEntityReturnsCorrectRole(): void
    {
        $owner = new Owner('2', 'o@p.com', 'p', 'O', 'P');
        
        $this->assertEquals('OWNER', $owner->getRole());
    }

    public function testOwnerEntityManagesOwnedParkingsCorrectly(): void
    {
        $owner = new Owner('3', 'owner@test.com', 'p', 'Test', 'Owner');
        
        $parking1 = $this->createMock(Parking::class);
        $parking2 = $this->createMock(Parking::class);
        
        // 1. Initialisation
        $this->assertEmpty($owner->getOwnedParkings(), "La liste doit être vide initialement.");
        
        // 2. Ajout de deux parkings distincts
        $owner->addParking($parking1);
        $owner->addParking($parking2);
        
        $ownedParkings = $owner->getOwnedParkings();

        // 3. Vérification de l'ajout
        $this->assertCount(2, $ownedParkings, "La liste doit contenir 2 parkings après l'ajout.");
        $this->assertSame($parking1, $ownedParkings[0], "Le premier parking doit être stocké correctement.");
        $this->assertSame($parking2, $ownedParkings[1], "Le second parking doit être stocké correctement.");

        // 4. Vérification de la règle métier (Anti-doublon)
        $owner->addParking($parking1);
        
        $this->assertCount(2, $owner->getOwnedParkings(), "Rajouter le même parking ne doit pas augmenter le compte.");
    }
}