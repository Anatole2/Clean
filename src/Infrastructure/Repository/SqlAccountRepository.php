<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Account;
use App\Domain\Entity\Owner;
use App\Domain\Entity\User;
use App\Domain\Repository\AccountRepositoryInterface;
use PDO;

class SqlAccountRepository implements AccountRepositoryInterface
{
  public function __construct(
    private PDO $connection
  ) {}

  public function save(Account $account): void
  {
    $sql = "INSERT INTO accounts (id, email, password_hash, first_name, last_name, role)
                VALUES (:id, :email, :pass, :first, :last, :role)
                ON DUPLICATE KEY UPDATE
                email = VALUES(email),
                password_hash = VALUES(password_hash),
                first_name = VALUES(first_name),
                last_name = VALUES(last_name)";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      'id' => $account->getId(),
      'email' => $account->getEmail(),
      'pass' => $account->getPasswordHash(),
      'first' => $account->getFirstName(),
      'last' => $account->getLastName(),
      'role' => $account->getRole()
    ]);
  }

  public function findByEmail(string $email): ?Account
  {
    $stmt = $this->connection->prepare("SELECT * FROM accounts WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      return null;
    }

    return $this->mapRowToEntity($row);
  }

  public function findById(string $id): ?Account
  {
    $stmt = $this->connection->prepare("SELECT * FROM accounts WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      return null;
    }

    return $this->mapRowToEntity($row);
  }

  /**
   * C'est ici qu'on décide si on crée un Owner ou un User
   */
  private function mapRowToEntity(array $row): Account
  {
    $role = $row['role'];

    if ($role === 'OWNER') {
      return Owner::reconstitute(
        $row['id'],
        $row['email'],
        $row['password_hash'],
        $row['first_name'],
        $row['last_name']
      );
    }

    if ($role === 'USER' || $role === 'DRIVER') {
      return User::reconstitute(
        $row['id'],
        $row['email'],
        $row['password_hash'],
        $row['first_name'],
        $row['last_name']
      );
    }

    throw new \Exception("Rôle inconnu en base de données : " . $role);
  }
}
