<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
  public function testUserRoleIsUser(): void
  {
    $user = User::create('u1', 'u@u.com', 'pass', 'F', 'L');

    // Vérifie que c'est bien USER (ou DRIVER selon ce que tu as gardé dans ton entité)
    // Adapte la chaîne ci-dessous si ton code renvoie "DRIVER"
    $this->assertEquals('USER', $user->getRole());
  }

  public function testUserFunctionality(): void
  {
    // Un petit test rapide pour s'assurer que l'héritage fonctionne aussi ici
    $user = User::create('u1', 'u@u.com', 'pass', 'Fan', 'Boy');

    $this->assertTrue($user->verifyPassword('pass'));
    $this->assertEquals('Fan', $user->getFirstName());
  }
}
