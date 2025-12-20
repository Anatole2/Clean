<?php

declare(strict_types=1);

namespace App\UseCase\Owner\GetUnauthorizedParkers;

class GetUnauthorizedParkersRequest
{
  public function __construct(
    public string $parkingId,
    public string $ownerId
  ) {}
}
