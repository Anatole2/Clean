<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Owner;
use PHPUnit\Framework\TestCase;

class OwnerTest extends TestCase
{
  public function testCreateHashesPassword(): void
  {
    $owner = Owner::create(
      'owner-1',
      'owner@test.com',
      'superSecret', // Mot de passe en clair
      'Jean',
      'Dupont'
    );

    // 1. On vérifie que le hash n'est PAS le mot de passe en clair
    $this->assertNotEquals('superSecret', $owner->getPasswordHash());

    // 2. On vérifie que le verifyPassword fonctionne
    $this->assertTrue($owner->verifyPassword('superSecret'));
    $this->assertFalse($owner->verifyPassword('wrongPassword'));

    // 3. On vérifie le rôle
    $this->assertEquals('OWNER', $owner->getRole());
  }

  public function testReconstituteRestoresStateWithoutHashingAgain(): void
  {
    // Simulation d'un hash existant en BDD
    $existingHash = password_hash('oldPassword', PASSWORD_DEFAULT);

    $owner = Owner::reconstitute(
      'owner-1',
      'owner@test.com',
      $existingHash,
      'Jean',
      'Dupont'
    );

    // Le hash doit être EXACTEMENT le même (pas de double hachage)
    $this->assertEquals($existingHash, $owner->getPasswordHash());

    // La vérification doit toujours marcher
    $this->assertTrue($owner->verifyPassword('oldPassword'));
  }

  public function testGetters(): void
  {
    // Ce test sert juste à monter le coverage à 100% sur les accesseurs simples
    $owner = Owner::reconstitute('id-1', 'email@test.com', 'hash', 'First', 'Last');

    $this->assertEquals('id-1', $owner->getId());
    $this->assertEquals('email@test.com', $owner->getEmail());
    $this->assertEquals('First', $owner->getFirstName());
    $this->assertEquals('Last', $owner->getLastName());
  }
}
