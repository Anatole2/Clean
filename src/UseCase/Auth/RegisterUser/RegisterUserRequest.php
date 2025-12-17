<?php

declare(strict_types=1);

namespace App\UseCase\Auth\RegisterUser;

class RegisterUserRequest
{
  public function __construct(
    public string $email,
    public string $password,
    public string $firstName,
    public string $lastName
  ) {}
}
