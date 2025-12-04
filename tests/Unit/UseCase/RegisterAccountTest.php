<?php

namespace Tests\Unit\UseCase;

use PHPUnit\Framework\TestCase;
use App\UseCase\Register\RegisterAccount;
use App\Domain\Entity\Account;
use App\Domain\Entity\User;
use App\Domain\Entity\Owner;
use App\Domain\Repository\AccountRepositoryInterface;
use Exception;

class RegisterAccountTest extends TestCase
{
    private AccountRepositoryInterface $mockRepository;
    private RegisterAccount $registerAccountUseCase;

    protected function setUp(): void
    {
        $this->mockRepository = $this->createMock(AccountRepositoryInterface::class);
        $this->registerAccountUseCase = new RegisterAccount($this->mockRepository);
    }

    public function testExecuteSuccessfullyRegistersNewDriver(): void
    {
        $this->mockRepository->expects($this->once())
             ->method('findByEmail')
             ->willReturn(null);

        $this->mockRepository->expects($this->once())
             ->method('save')
             ->with(
                $this->callback(function (Account $account) {
                    return $account->getRole() === 'DRIVER';
                })
             );

        $result = $this->registerAccountUseCase->execute(
            'DRIVER',
            'driver@new.com',
            'securepass',
            'Alice',
            'Driver'
        );

        $this->assertIsArray($result);
        $this->assertEquals('DRIVER', $result['role']);
        $this->assertEquals('driver@new.com', $result['email']);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertNotEmpty($result['user_id']);
    }

    public function testExecuteSuccessfullyRegistersNewOwner(): void
    {
        $this->mockRepository->expects($this->once())
             ->method('findByEmail')
             ->willReturn(null);

        $this->mockRepository->expects($this->once())
             ->method('save')
             ->with(
                $this->callback(function (Account $account) {
                    return $account->getRole() === 'OWNER';
                })
             );

        $result = $this->registerAccountUseCase->execute(
            'OWNER',
            'owner@new.com',
            'pass4owner',
            'Bob',
            'Owner'
        );

        $this->assertEquals('OWNER', $result['role']);
        $this->assertEquals('owner@new.com', $result['email']);
    }

    public function testExecuteThrowsExceptionIfEmailAlreadyExists(): void
    {
        $existingAccount = $this->createMock(Owner::class);
        $this->mockRepository->expects($this->once())
             ->method('findByEmail')
             ->willReturn($existingAccount);

        $this->mockRepository->expects($this->never())
             ->method('save');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("L'adresse email est déjà utilisée.");

        $this->registerAccountUseCase->execute(
            'DRIVER',
            'existing@email.com',
            'password',
            'Exist',
            'User'
        );
    }

    public function testExecuteThrowsExceptionIfInvalidRoleIsProvided(): void
    {
        $this->mockRepository->expects($this->once())
             ->method('findByEmail')
             ->willReturn(null);

        $this->mockRepository->expects($this->never())
             ->method('save');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Rôle invalide spécifié.");

        $this->registerAccountUseCase->execute(
            'ADMIN',
            'admin@test.com',
            'adminpass',
            'Admin',
            'Role'
        );
    }
}