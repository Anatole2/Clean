<?php

namespace Tests\Unit\UseCase\Account;

use PHPUnit\Framework\TestCase;
use App\UseCase\Account\LoginAccount;
use App\Domain\Entity\User;
use App\Domain\Repository\AccountRepositoryInterface;
use App\Infrastructure\Security\JwtService;
use Exception;

class LoginAccountTest extends TestCase
{
    private AccountRepositoryInterface $mockRepository;
    private JwtService $mockJwtService;
    private LoginUser $loginUserUseCase;

    protected function setUp(): void
    {
        $this->mockRepository = $this->createMock(AccountRepositoryInterface::class);
        $this->mockJwtService = $this->createMock(JwtService::class);
        
        $this->loginUserUseCase = new LoginUser($this->mockRepository, $this->mockJwtService);
    }

    public function testExecuteSuccessfullyLogsInUserAndGeneratesToken(): void
    {
        $email = 'test@driver.com';
        $password = 'correctpassword';
        $userId = 'uuid-test-123';
        $token = 'dummy-jwt-token';
        
        $mockUser = $this->createMock(User::class);
        
        $mockUser->method('getId')->willReturn($userId);
        $mockUser->method('getRole')->willReturn('DRIVER');
        $mockUser->method('getFirstName')->willReturn('Alex');
        $mockUser->method('getEmail')->willReturn($email);
        
        $mockUser->expects($this->once())
                 ->method('verifyPassword')
                 ->with($password)
                 ->willReturn(true);
        
        $this->mockRepository->expects($this->once())
             ->method('findByEmail')
             ->with($email)
             ->willReturn($mockUser);

        $this->mockJwtService->expects($this->once())
                             ->method('generateToken')
                             ->with($mockUser)
                             ->willReturn($token);

        $result = $this->loginUserUseCase->execute($email, $password);

        $this->assertIsArray($result);
        $this->assertEquals($token, $result['token']);
        $this->assertEquals($userId, $result['user_id']);
        $this->assertEquals('DRIVER', $result['role']);
        $this->assertEquals('Alex', $result['first_name']);
    }

    public function testExecuteThrowsExceptionIfEmailNotFound(): void
    {
        $email = 'unknown@user.com';
        
        $this->mockRepository->expects($this->once())
             ->method('findByEmail')
             ->with($email)
             ->willReturn(null);

        $this->mockJwtService->expects($this->never())
                             ->method('generateToken');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid credentials.");

        $this->loginUserUseCase->execute($email, 'anypassword');
    }

    public function testExecuteThrowsExceptionIfPasswordIsIncorrect(): void
    {
        $email = 'test@driver.com';
        $wrongPassword = 'wrongpassword';
        $correctPassword = 'correctpassword';
        
        $mockUser = $this->createMock(User::class);
        
        $mockUser->expects($this->once())
                 ->method('verifyPassword')
                 ->with($wrongPassword)
                 ->willReturn(false);
        
        $this->mockRepository->expects($this->once())
             ->method('findByEmail')
             ->with($email)
             ->willReturn($mockUser);

        $this->mockJwtService->expects($this->never())
                             ->method('generateToken');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid credentials.");

        $this->loginUserUseCase->execute($email, $wrongPassword);
    }
}