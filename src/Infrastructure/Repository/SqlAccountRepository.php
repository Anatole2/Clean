<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Account;
use App\Domain\Entity\Owner;
use App\Domain\Entity\User;
use App\Domain\Repository\AccountRepositoryInterface;
use PDO;
use PDOException;
use Exception;

class SqlAccountRepository implements AccountRepositoryInterface
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function save(Account $account): void
    {
        $sql = "INSERT INTO accounts (id, email, password_hash, first_name, last_name, role) 
                VALUES (:id, :email, :password_hash, :first_name, :last_name, :role)";
        try {
            $request = $this->connection->prepare($sql);
            $request->execute([
                ':id' => $account->getId(),
                ':email' => $account->getEmail(),
                ':password_hash' => $account->getPasswordHash(),
                ':first_name' => $account->getFirstName(),
                ':last_name' => $account->getLastName(),
                ':role' => $account->getRole()
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { 
                 throw new Exception("L'adresse email est déjà utilisée.");
            }
            
            throw new Exception("Erreur de persistance: Échec de la sauvegarde du compte.");
        }
    }

    public function findByEmail(string $email): ?Account
    {
        $sql = "SELECT * role 
                FROM accounts 
                WHERE email = :email";

        try {
            $request = $this->connection->prepare($sql);
            $request->execute([':email' => $email]);
            $data = $request->fetch(PDO::FETCH_ASSOC);

            if ($data === false) {
                return null;
            }

            return $this->hydrateAccount($data);

        } catch (PDOException $e) {
            throw new Exception("Erreur de base de données lors de la recherche par email.");
        }
    }

    private function hydrateAccount(array $data): ?Account
    {
        switch ($data['role']) {
            case 'OWNER':
                return Owner::reconstitute(
                    $data['id'], 
                    $data['email'], 
                    $data['password_hash'], 
                    $data['first_name'], 
                    $data['last_name']
                );
            case 'DRIVER':
                return User::reconstitute(
                    $data['id'], 
                    $data['email'], 
                    $data['password_hash'], 
                    $data['first_name'], 
                    $data['last_name']
                );
            default:
                return null;
        }
    }
}