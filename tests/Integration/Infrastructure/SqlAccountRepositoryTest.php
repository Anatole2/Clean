<?php

namespace Tests\Integration\Infrastructure\Repository;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Repository\SqlAccountRepository;
use App\Domain\Entity\User;
use App\Domain\Entity\Owner;
use PDO;
use PDOStatement;
use PDOException;
use Exception;

class SqlAccountRepositoryTest extends TestCase
{
    private PDO $mockConnection;
    private PDOStatement $mockStatement;
    private SqlAccountRepository $repository;

    protected function setUp(): void
    {
        $this->mockStatement = $this->createMock(PDOStatement::class);

        $this->mockConnection = $this->createMock(PDO::class);

        $this->mockConnection->method('setAttribute');

        $this->mockConnection->method('prepare')
                             ->willReturn($this->mockStatement);

        $this->repository = new SqlAccountRepository($this->mockConnection);
    }

    // =========================================================================
    // TESTS DE LA MÉTHODE SAVE (INSERT)
    // =========================================================================

    public function testSaveSuccessfullyInsertsNewOwnerAccount(): void
    {
        $owner = Owner::reconstitute('uuid-1', 'owner@test.com', 'hash', 'O', 'W');

        $this->mockStatement->expects($this->once())
                            ->method('execute')
                            ->with($this->callback(function ($params) use ($owner) {
                                $this->assertEquals($owner->getId(), $params[':id']);
                                $this->assertEquals($owner->getEmail(), $params[':email']);
                                $this->assertEquals('OWNER', $params[':role']);
                                return true;
                            }))
                            ->willReturn(true);

        $this->repository->save($owner);
    }

    public function testSaveThrowsExceptionOnDuplicateEmail(): void
    {
        $user = User::reconstitute('uuid-2', 'user@test.com', 'hash', 'U', 'S');

        $this->mockStatement->method('execute')
                            ->willThrowException(new PDOException("Duplicate key", '23000'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("L'adresse email est déjà utilisée.");

        $this->repository->save($user);
    }

    // =========================================================================
    // TESTS DE LA MÉTHODE FIND_BY_EMAIL (SELECT)
    // =========================================================================

    public function testFindByEmailReturnsNullWhenAccountIsNotFound(): void
    {
        $this->mockStatement->expects($this->once())
                            ->method('fetch')
                            ->willReturn(false);

        $result = $this->repository->findByEmail('nonexistent@email.com');
        $this->assertNull($result);
    }

    public function testFindByEmailSuccessfullyReturnsHydratedUserEntity(): void
    {
        $dbData = [
            'id' => 'uuid-3',
            'email' => 'driver@found.com',
            'password_hash' => 'user_hash_123',
            'first_name' => 'Jean',
            'last_name' => 'Driver',
            'role' => 'DRIVER'
        ];

        $this->mockStatement->expects($this->once())
                            ->method('fetch')
                            ->willReturn($dbData);
                            
        $account = $this->repository->findByEmail('driver@found.com');

        $this->assertInstanceOf(User::class, $account, "Doit être hydraté comme une entité User.");
        $this->assertEquals($dbData['id'], $account->getId());
        $this->assertEquals($dbData['role'], $account->getRole());
        $this->assertTrue($account->verifyPassword('password_en_clair_dummy'), "La vérification doit être gérée par l'entité.");
    }
    
    public function testFindByEmailSuccessfullyReturnsHydratedOwnerEntity(): void
    {
        $dbData = [
            'id' => 'uuid-4',
            'email' => 'owner@found.com',
            'password_hash' => 'owner_hash_456',
            'first_name' => 'Sophie',
            'last_name' => 'Owner',
            'role' => 'OWNER'
        ];

        $this->mockStatement->expects($this->once())
                            ->method('fetch')
                            ->willReturn($dbData);
                        
        $account = $this->repository->findByEmail('owner@found.com');

        $this->assertInstanceOf(Owner::class, $account, "Doit être hydraté comme une entité Owner.");
        $this->assertEquals($dbData['role'], $account->getRole());
    }

    public function testFindByEmailThrowsExceptionOnDatabaseError(): void
    {
        $this->mockConnection->method('prepare')
                            ->willThrowException(new PDOException("Connexion perdue.", 'HY000'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Erreur de base de données lors de la recherche par email.");

        $this->repository->findByEmail('error@test.com');
    }
}