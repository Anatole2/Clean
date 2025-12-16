<?php

declare(strict_types=1);

namespace App\UseCase\Auth\RegisterOwner;

class RegisterOwnerRequest
{
  public function __construct(
    public string $email,
    public string $password,
    public string $firstName,
    public string $lastName
  ) {}
}
