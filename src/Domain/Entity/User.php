<?php

declare(strict_types=1);

namespace App\Domain\Entity;

class User extends Account
{
  public function getRole(): string
  {
    return "USER";
  }
}
