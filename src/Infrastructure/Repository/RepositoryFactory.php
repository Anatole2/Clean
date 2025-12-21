<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use MongoDB\Client;
use PDO;
use RuntimeException;
use Psr\Container\ContainerInterface; // Pour le typage du container

class RepositoryFactory
{
  // On peut typer $container avec ContainerInterface si ton conteneur est compatible PSR-11,
  // sinon laisse simplement "object|array" ou pas de type si c'est Pimple.
  public function __construct(private $container) {}

  public function create(string $interfaceName): object
  {
    // 1. Détecter le type de connexion souhaité (.env)
    $connectionType = getenv('DB_CONNECTION') ?: 'mysql';

    // 2. Extraire le nom de base (ex: "AccountRepository") depuis l'interface
    $shortName = (new \ReflectionClass($interfaceName))->getShortName();
    $baseName = str_replace('Interface', '', $shortName);

    // 3. Définir les noms de classe attendus
    // Note le namespace : on cherche dans le même dossier (Infrastructure\Repository)
    $sqlClass = "App\\Infrastructure\\Repository\\Sql{$baseName}";
    $mongoClass = "App\\Infrastructure\\Repository\\Mongo{$baseName}";

    // 4. Logique Mongo
    if ($connectionType === 'mongodb') {
      if (class_exists($mongoClass)) {
        return $this->createMongoRepository($mongoClass);
      }
    }

    // 5. Logique SQL (Par défaut)
    if (class_exists($sqlClass)) {
      return $this->createSqlRepository($sqlClass);
    }

    throw new RuntimeException("Aucune implémentation trouvée pour $interfaceName");
  }

  private function createSqlRepository(string $className): object
  {
    // On accède au conteneur comme un tableau (Pimple style)
    $pdo = ($this->container)[PDO::class]();
    return new $className($pdo);
  }

  private function createMongoRepository(string $className): object
  {
    $client = ($this->container)[Client::class]();

    $isTestMode = isset($_SERVER['HTTP_X_TEST_MODE']) && $_SERVER['HTTP_X_TEST_MODE'] === 'true';
    $dbName = $isTestMode
      ? (getenv('MONGO_TEST_DATABASE') ?: 'parking_db_mongo_test')
      : (getenv('MONGO_DATABASE') ?: 'parking_db_mongo');

    return new $className($client, $dbName);
  }
}
