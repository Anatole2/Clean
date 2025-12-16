<?php

declare(strict_types=1);

namespace App\UseCase\Auth\Login;

use App\Domain\Entity\Account;

class LoginResponse
{
  public function __construct(
    public string $token,
    public Account $account
  ) {}
}
