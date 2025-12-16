<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Repository;

use App\Domain\Entity\Owner;
use App\Domain\Entity\User;
use App\Infrastructure\Repository\SqlAccountRepository;
use Tests\Integration\IntegrationTestCase;

class SqlAccountRepositoryTest extends IntegrationTestCase

{
  private SqlAccountRepository $repository;

  protected function setUp(): void
  {
    // Appel du setUp parent pour initialiser la BDD
    parent::setUp();

    // On initialise juste le repo spécifique à ce test
    $this->repository = new SqlAccountRepository($this->pdo);
  }

  public function testSaveAndFindOwner(): void
  {
    // 1. Création d'un Owner via la Factory (qui hache le mdp)
    $owner = Owner::create(
      'owner-id-1',
      'owner@test.com',
      'securePassword',
      'Alice',
      'Owner'
    );

    // 2. Sauvegarde
    $this->repository->save($owner);

    // 3. Récupération par Email
    $retrieved = $this->repository->findByEmail('owner@test.com');

    // 4. Assertions
    $this->assertNotNull($retrieved);
    $this->assertInstanceOf(Owner::class, $retrieved, "L'objet récupéré doit être une instance de Owner");
    $this->assertEquals('owner-id-1', $retrieved->getId());
    $this->assertEquals('Alice', $retrieved->getFirstName());
    $this->assertEquals('OWNER', $retrieved->getRole());

    // Vérification que le mot de passe est bien haché et valide
    $this->assertTrue($retrieved->verifyPassword('securePassword'));
    $this->assertFalse($retrieved->verifyPassword('wrongPassword'));
  }

  public function testSaveAndFindUser(): void
  {
    // On teste aussi le User pour être sûr que le polymorphisme marche
    $user = User::create(
      'user-id-1',
      'driver@test.com',
      'vroum',
      'Bob',
      'Driver'
    );

    $this->repository->save($user);

    $retrieved = $this->repository->findById('user-id-1');

    $this->assertNotNull($retrieved);
    $this->assertInstanceOf(User::class, $retrieved, "L'objet doit être un User");
    $this->assertEquals('USER', $retrieved->getRole());
  }

  public function testReturnNullIfNotFound(): void
  {
    $this->assertNull($this->repository->findByEmail('ghost@test.com'));
    $this->assertNull($this->repository->findById('ghost-id'));
  }
  public function testFindThrowsExceptionOnUnknownRole(): void
  {
    // 1. On insère manuellement une ligne avec un rôle pourri via PDO direct
    // (On contourne la méthode save() pour forcer l'erreur)
    $this->pdo->exec("
            INSERT INTO accounts (id, email, password_hash, first_name, last_name, role) 
            VALUES ('buggy-id', 'bug@test.com', 'hash', 'Bug', 'Man', 'ALIEN')
        ");

    // 2. On s'attend à ce que le Repository plante en essayant de le lire
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Rôle inconnu en base de données : ALIEN");

    // 3. On déclenche la lecture
    $this->repository->findByEmail('bug@test.com');
  }
}
