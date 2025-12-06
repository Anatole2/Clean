<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Domain\Service\IdGeneratorInterface;
use Ramsey\Uuid\Uuid;

class RamseyIdGenerator implements IdGeneratorInterface
{
  public function generate(): string
  {
    // Génère un UUID v4 aléatoire
    return Uuid::uuid4()->toString();
  }
}
