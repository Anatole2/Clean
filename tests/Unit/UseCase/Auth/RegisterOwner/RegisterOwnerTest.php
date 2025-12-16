<?php

declare(strict_types=1);

namespace App\Tests\Unit\UseCase\Auth\RegisterOwner;

use App\Domain\Entity\Owner;
use App\Domain\Repository\AccountRepositoryInterface;
use App\Domain\Service\IdGeneratorInterface;
use App\UseCase\Auth\RegisterOwner\RegisterOwner;
use App\UseCase\Auth\RegisterOwner\RegisterOwnerRequest;
use App\UseCase\Auth\RegisterOwner\RegisterOwnerResponse;
use PHPUnit\Framework\TestCase;

class RegisterOwnerTest extends TestCase
{
  public function testExecuteCreatesOwnerWhenEmailIsAvailable(): void
  {
    // 1. Mocks
    $repository = $this->createMock(AccountRepositoryInterface::class);
    $idGenerator = $this->createMock(IdGeneratorInterface::class);
    /** @var IdGeneratorInterface&\PHPUnit\Framework\MockObject\MockObject $idGenerator */
    /** @var AccountRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repository */

    // Configuration
    $idGenerator->method('generate')->willReturn('owner-123');

    // Le repo ne trouve personne avec cet email (donc c'est libre)
    $repository->method('findByEmail')->willReturn(null);

    // On s'attend à ce que la méthode save() soit appelée une fois avec un objet Owner
    $repository->expects($this->once())
      ->method('save')
      ->with($this->isInstanceOf(Owner::class));

    // 2. Exécution
    $useCase = new RegisterOwner($repository, $idGenerator);

    $request = new RegisterOwnerRequest(
      'test@owner.com',
      'password123', // Le mot de passe en clair
      'Jean',
      'Dupont'
    );

    $response = $useCase->execute($request);

    // 3. Vérifications
    $this->assertInstanceOf(RegisterOwnerResponse::class, $response);
    $this->assertEquals('owner-123', $response->id);
    $this->assertEquals('test@owner.com', $response->email);
    $this->assertEquals('Jean', $response->firstName);

    // Vérification de sécurité : le response ne doit PAS avoir de propriété password
    $this->assertObjectNotHasProperty('password', $response);
    $this->assertObjectNotHasProperty('passwordHash', $response);
  }

  public function testExecuteThrowsExceptionIfEmailAlreadyExists(): void
  {
    $repository = $this->createMock(AccountRepositoryInterface::class);
    $idGenerator = $this->createMock(IdGeneratorInterface::class);

    // Simulation : findByEmail renvoie DÉJÀ un compte existant
    $existingOwner = $this->createMock(Owner::class);
    /** @var IdGeneratorInterface&\PHPUnit\Framework\MockObject\MockObject $idGenerator */
    /** @var AccountRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repository */
    $repository->method('findByEmail')->willReturn($existingOwner);

    // On s'attend à une erreur
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Cet email est déjà utilisé.");

    // Exécution
    $useCase = new RegisterOwner($repository, $idGenerator);
    $useCase->execute(new RegisterOwnerRequest('exist@test.com', 'pass', 'J', 'D'));
  }
}
