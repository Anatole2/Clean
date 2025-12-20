<?php

declare(strict_types=1);

namespace Tests\Functional;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use PDO;

class FunctionalTestCase extends TestCase
{
  protected Client $client;
  protected PDO $pdo;
  protected string $baseUrl = 'http://localhost/public'; // Port 80 interne au conteneur

  protected function setUp(): void
  {
    // 1. Configuration BDD (Mêmes variables que IntegrationTestCase)
    $host = $_ENV['DB_TEST_HOST'] ?? 'mysql';
    $db   = $_ENV['DB_TEST_DATABASE'] ?? 'sharedParkingTest';
    $user = $_ENV['DB_TEST_USER'] ?? 'root';
    $pass = $_ENV['DB_TEST_PASSWORD'] ?? 'root';

    // 2. Connexion PDO pour le nettoyage
    try {
      $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $db);
      $this->pdo = new PDO($dsn, $user, $pass);
      $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (\PDOException $e) {
      $this->fail("Erreur connexion BDD Test : " . $e->getMessage());
    }

    // 3. Nettoyage (Ta logique réutilisée)
    $this->resetDatabase();

    // 4. Configuration du Client HTTP avec le HEADER MAGIQUE
    $this->client = new Client([
      'base_uri' => $this->baseUrl,
      'http_errors' => false,
      'cookies' => true,
      'headers' => [
        'X-Test-Mode' => 'true' // <--- C'est la clé !
      ]
    ]);
  }

  private function resetDatabase(): void
  {
    $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $this->pdo->exec("TRUNCATE TABLE parking_sessions");
    $this->pdo->exec("TRUNCATE TABLE reservations");
    $this->pdo->exec("TRUNCATE TABLE user_subscriptions");
    $this->pdo->exec("TRUNCATE TABLE parkings");
    $this->pdo->exec("TRUNCATE TABLE accounts");
    $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Optionnel : Si tu veux être sûr que la structure est là,
    // tu peux appeler initSchema() ici comme dans ton IntegrationTestCase.
  }
}
