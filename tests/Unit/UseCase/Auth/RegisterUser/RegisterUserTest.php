<?php

declare(strict_types=1);

namespace App\Tests\Unit\UseCase\Auth\RegisterUser;

use App\Domain\Entity\User;
use App\Domain\Repository\AccountRepositoryInterface;
use App\Domain\Service\IdGeneratorInterface;
use App\UseCase\Auth\RegisterUser\RegisterUser;
use App\UseCase\Auth\RegisterUser\RegisterUserRequest;
use App\UseCase\Auth\RegisterUser\RegisterUserResponse;
use PHPUnit\Framework\TestCase;

class RegisterUserTest extends TestCase
{
  public function testExecuteCreatesUserWhenEmailIsAvailable(): void
  {
    // 1. Mocks
    $repository = $this->createMock(AccountRepositoryInterface::class);
    $idGenerator = $this->createMock(IdGeneratorInterface::class);
    /** @var IdGeneratorInterface&\PHPUnit\Framework\MockObject\MockObject $idGenerator */
    /** @var AccountRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repository */

    // Configuration
    $idGenerator->method('generate')->willReturn('user-123');

    // Le repo ne trouve personne avec cet email (donc c'est libre)
    $repository->method('findByEmail')->willReturn(null);

    // On s'attend à ce que la méthode save() soit appelée une fois avec un objet User
    $repository->expects($this->once())
      ->method('save')
      ->with($this->isInstanceOf(User::class));

    // 2. Exécution
    $useCase = new RegisterUser($repository, $idGenerator);

    $request = new RegisterUserRequest(
      'test@user.com',
      'password123',
      'Jean',
      'Dupont'
    );

    $response = $useCase->execute($request);

    // 3. Vérifications
    $this->assertInstanceOf(RegisterUserResponse::class, $response);
    $this->assertEquals('user-123', $response->id);
    $this->assertEquals('test@user.com', $response->email);
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
    $existingUser = $this->createMock(User::class);
    /** @var IdGeneratorInterface&\PHPUnit\Framework\MockObject\MockObject $idGenerator */
    /** @var AccountRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repository */
    $repository->method('findByEmail')->willReturn($existingUser);

    // On s'attend à une erreur
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Cet email est déjà utilisé.");

    // Exécution
    $useCase = new RegisterUser($repository, $idGenerator);
    $useCase->execute(new RegisterUserRequest('exist@test.com', 'pass', 'J', 'D'));
  }
}
