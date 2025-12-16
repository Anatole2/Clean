<?php

declare(strict_types=1);

namespace App\Tests\Unit\UseCase\Auth\Login;

use App\Domain\Entity\Account;
use App\Domain\Repository\AccountRepositoryInterface;
use App\Infrastructure\Security\JwtService;
use App\UseCase\Auth\Login\Login;
use App\UseCase\Auth\Login\LoginRequest;
use App\UseCase\Auth\Login\LoginResponse;
use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase
{
  public function testExecuteReturnsTokenWhenCredentialsAreValid(): void
  {
    // 1. Préparation des Mocks
    $repository = $this->createMock(AccountRepositoryInterface::class);
    $jwtService = $this->createMock(JwtService::class);
    $account = $this->createMock(Account::class);
    /** @var AccountRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repository */
    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject $jwtService */

    // 2. Configuration du scénario "Succès"

    // Le repository trouve un compte
    $repository->expects($this->once())
      ->method('findByEmail')
      ->with('test@test.com')
      ->willReturn($account);

    // Le compte valide le mot de passe
    $account->expects($this->once())
      ->method('verifyPassword')
      ->with('password123')
      ->willReturn(true);

    // Le service JWT génère un token
    $jwtService->expects($this->once())
      ->method('generateToken')
      ->with($account)
      ->willReturn('fake-jwt-token');

    // 3. Exécution
    $useCase = new Login($repository, $jwtService);
    $request = new LoginRequest('test@test.com', 'password123');
    $response = $useCase->execute($request);

    // 4. Assertions
    $this->assertInstanceOf(LoginResponse::class, $response);
    $this->assertEquals('fake-jwt-token', $response->token);
    $this->assertSame($account, $response->account);
  }

  public function testExecuteThrowsExceptionIfUserNotFound(): void
  {
    $repository = $this->createMock(AccountRepositoryInterface::class);
    $jwtService = $this->createMock(JwtService::class);
    /** @var AccountRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repository */
    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject $jwtService */

    // Le repository ne trouve RIEN (null)
    $repository->method('findByEmail')->willReturn(null);

    // On s'attend à une erreur
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Email ou mot de passe incorrect.");

    $useCase = new Login($repository, $jwtService);
    $useCase->execute(new LoginRequest('unknown@test.com', 'pass'));
  }

  public function testExecuteThrowsExceptionIfPasswordIsInvalid(): void
  {
    $repository = $this->createMock(AccountRepositoryInterface::class);
    $jwtService = $this->createMock(JwtService::class);
    $account = $this->createMock(Account::class);
    /** @var AccountRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject $repository */
    /** @var JwtService&\PHPUnit\Framework\MockObject\MockObject $jwtService */

    // Le repository trouve le compte
    $repository->method('findByEmail')->willReturn($account);

    // MAIS le compte dit que le mot de passe est faux
    $account->method('verifyPassword')->willReturn(false);

    // On ne doit JAMAIS générer de token dans ce cas
    $jwtService->expects($this->never())->method('generateToken');

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Email ou mot de passe incorrect.");

    $useCase = new Login($repository, $jwtService);
    $useCase->execute(new LoginRequest('test@test.com', 'wrong-pass'));
  }
}
