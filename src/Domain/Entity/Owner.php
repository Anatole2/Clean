<?php

declare(strict_types=1);

namespace App\Domain\Entity;

class Owner extends Account
{
  public function getRole(): string
  {
    return "OWNER";
  }
}
