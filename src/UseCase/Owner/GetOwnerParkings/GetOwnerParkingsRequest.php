<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetOwnerParkings;

class GetOwnerParkingsRequest
{
  public function __construct(
    public string $ownerId
  ) {}
}
