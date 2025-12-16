<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Account;

interface AccountRepositoryInterface
{
  public function save(Account $account): void;
  public function findByEmail(string $email): ?Account;
  public function findById(string $id): ?Account;
}
