<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Account;
use App\Domain\Entity\Owner;
use App\Domain\Entity\User;
use App\Domain\Repository\AccountRepositoryInterface;
use MongoDB\Client;
use MongoDB\Collection;

class MongoAccountRepository implements AccountRepositoryInterface
{
  private Collection $collection;

  public function __construct(Client $client, string $databaseName)
  {
    // On sélectionne la base de données (prod ou test) et la collection 'accounts'
    $this->collection = $client->selectDatabase($databaseName)->selectCollection('accounts');
  }

  public function save(Account $account): void
  {
    // 1. Préparation des données
    $data = [
      '_id' => $account->getId(),
      'email' => $account->getEmail(),
      'password_hash' => $account->getPasswordHash(),
      'first_name' => $account->getFirstName(),
      'last_name' => $account->getLastName(),
      'role' => $account->getRole()
    ];

    // 2. Upsert (Insert ou Update)
    // Si l'ID existe, on met à jour ($set), sinon on crée.
    $this->collection->updateOne(
      ['_id' => $account->getId()],
      ['$set' => $data],
      ['upsert' => true]
    );
  }

  public function findByEmail(string $email): ?Account
  {
    // Recherche par le champ 'email'
    $document = $this->collection->findOne(['email' => $email]);

    if (!$document) {
      return null;
    }

    return $this->mapDocumentToEntity((array) $document);
  }

  public function findById(string $id): ?Account
  {
    // Recherche par la clé primaire '_id'
    $document = $this->collection->findOne(['_id' => $id]);

    if (!$document) {
      return null;
    }

    return $this->mapDocumentToEntity((array) $document);
  }

  /**
   * Conversion Document BSON -> Entité PHP
   * C'est la même logique que ton mapRowToEntity SQL
   */
  private function mapDocumentToEntity(array $doc): Account
  {
    $role = $doc['role'];

    // Extraction des données (avec _id mappé vers l'ID de l'entité)
    $id = $doc['_id'];
    $email = $doc['email'];
    $hash = $doc['password_hash'];
    $firstName = $doc['first_name'] ?? ''; // Sécurité null coalescing
    $lastName = $doc['last_name'] ?? '';

    if ($role === 'OWNER') {
      return Owner::reconstitute(
        $id,
        $email,
        $hash,
        $firstName,
        $lastName
      );
    }

    if ($role === 'USER' || $role === 'DRIVER') {
      return User::reconstitute(
        $id,
        $email,
        $hash,
        $firstName,
        $lastName
      );
    }

    throw new \Exception("Rôle inconnu en base Mongo : " . $role);
  }
}
