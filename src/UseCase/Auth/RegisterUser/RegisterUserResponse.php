<?php

declare(strict_types=1);

namespace App\UseCase\Auth\RegisterUser;

class RegisterUserResponse
{
  public function __construct(
    public string $id,
    public string $email,
    public string $firstName,
    public string $lastName
  ) {}
}
