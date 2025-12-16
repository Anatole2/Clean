<?php

declare(strict_types=1);

namespace App\UseCase\Auth\Login;

class LoginRequest
{
  public function __construct(
    public string $email,
    public string $password,
  ) {}
}
