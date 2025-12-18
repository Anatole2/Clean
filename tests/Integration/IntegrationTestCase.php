<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;

abstract class IntegrationTestCase extends TestCase
{
  protected PDO $pdo;

  /**
   * Cette méthode se lance automatiquement avant CHAQUE test des enfants
   */
  protected function setUp(): void
  {
    // 1. Configuration (Récupération des variables d'env)
    $host = $_ENV['DB_TEST_HOST'] ?? 'mysql';
    $db   = $_ENV['DB_TEST_DATABASE'] ?? 'sharedParkingTest';
    $user = $_ENV['DB_TEST_USER'] ?? 'root';
    $pass = $_ENV['DB_TEST_PASSWORD'] ?? 'root';

    // 2. Sécurité
    if ($db !== 'sharedParkingTest') {
      $this->markTestSkipped("ALERTE SÉCURITÉ : Mauvaise base de test ($db).");
    }

    // 3. Connexion
    $dsn = sprintf('mysql:host=%s;dbname=%s', $host, $db);
    $this->pdo = new PDO($dsn, $user, $pass);
    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 4. Initialisation du Schéma (Global pour tout le monde)
    $this->initSchema();

    // 5. Nettoyage de TOUTES les tables (Pour être sûr)
    // Désactive les clés étrangères pour pouvoir truncate dans n'importe quel ordre
    $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $this->pdo->exec("TRUNCATE TABLE parkings");
    $this->pdo->exec("TRUNCATE TABLE accounts");
    $this->pdo->exec("TRUNCATE TABLE reservations");
    $this->pdo->exec("TRUNCATE TABLE user_subscriptions");
    $this->pdo->exec("TRUNCATE TABLE parking_sessions");
    $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
  }

  private function initSchema(): void
  {
    // On remonte de 2 niveaux (Integration/tests/) pour aller chercher database/
    $schemaPath = __DIR__ . '/../../database/schema.sql';

    if (!file_exists($schemaPath)) {
      throw new \Exception("Schema introuvable : " . $schemaPath);
    }

    // On désactive les vérifications de clés étrangères pour pouvoir supprimer sans ordre précis
    $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // On supprime proprement les tables si elles existent déjà
    $this->pdo->exec("DROP TABLE IF EXISTS parkings");
    $this->pdo->exec("DROP TABLE IF EXISTS accounts");
    $this->pdo->exec("DROP TABLE IF EXISTS reservations");
    $this->pdo->exec("DROP TABLE IF EXISTS user_subscriptions");
    $this->pdo->exec("DROP TABLE IF EXISTS parking_sessions");
    $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $sql = file_get_contents($schemaPath);
    $this->pdo->exec($sql);
  }
}
