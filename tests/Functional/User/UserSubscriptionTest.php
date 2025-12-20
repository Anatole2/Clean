<?php

declare(strict_types=1);

namespace Tests\Functional\User;

use Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;

class UserSubscriptionTest extends FunctionalTestCase
{
  public function testUserCanSubscribeAndEnter(): void
  {
    // 1. NETTOYAGE
    $this->pdo->exec("DELETE FROM user_subscriptions");
    $this->pdo->exec("DELETE FROM parking_sessions");
    $this->pdo->exec("DELETE FROM reservations");
    $this->pdo->exec("DELETE FROM parkings");
    $this->pdo->exec("DELETE FROM accounts");

    // 2. SETUP : Création du Propriétaire
    $this->pdo->exec("INSERT INTO accounts (id, email, password_hash, role) VALUES ('owner-sub', 'owner@sub.com', 'hash', 'OWNER')");

    // 3. SETUP : Création du Parking (Ouvert 24/7)
    $priceGrid = json_encode(['60' => 200]);

    // Horaires d'ouverture du parking : Lundi(1) à Dimanche(7)
    $openingHours = json_encode([
      [
        "startDay" => "1",
        "startTime" => "00:00",
        "endDay" => "7",
        "endTime" => "23:59"
      ]
    ]);

    // Plan d'abonnement (Offre Week-End)
    $plans = json_encode([
      [
        "id" => "plan-week-end",
        "name" => "Forfait Test Week End",
        "price" => 7000,
        "schedule" => [
          [
            "startDay" => "6", // Samedi
            "startTime" => "00:01",
            "endDay" => "7",   // Dimanche
            "endTime" => "23:59"
          ]
        ]
      ]
    ]);

    $stmt = $this->pdo->prepare("INSERT INTO parkings (id, owner_id, name, latitude, longitude, total_places, price_grid, opening_hours, subscription_plans) 
            VALUES ('p-sub', 'owner-sub', 'Parking Abonnement', 48.85, 2.35, 10, :price, :hours, :plans)");

    $stmt->execute([
      'price' => $priceGrid,
      'hours' => $openingHours,
      'plans' => $plans
    ]);

    // 4. INSCRIPTION & LOGIN USER
    $this->client->post('/register/user', [
      'json' => ['email' => 'sub@test.com', 'password' => 'pass', 'firstName' => 'Bob', 'lastName' => 'Abo', 'role' => 'USER']
    ]);
    $this->client->post('/login', [
      'json' => ['email' => 'sub@test.com', 'password' => 'pass']
    ]);

    // 5. SOUSCRIPTION (API)
    // On utilise des dates futures valides pour que le UseCase accepte la demande
    $futureStart = (new DateTimeImmutable('+1 day'))->format('Y-m-d');
    $futureEnd   = (new DateTimeImmutable('+60 days'))->format('Y-m-d');

    $res = $this->client->post('/parkings/p-sub/subscribe', [
      'json' => [
        'plan_id'    => 'plan-week-end',
        'start_date' => $futureStart,
        'end_date'   => $futureEnd
      ],
      'headers' => ['Accept' => 'application/json']
    ]);

    $this->assertTrue(in_array($res->getStatusCode(), [200, 201]), "Échec de la souscription API");

    // 6. SIMULATION PAIEMENT & ACTIVATION (Patch SQL)
    // On passe l'abonnement à "Actif" (is_active = 1)
    // On définit un emploi du temps "Universel" (24/7) pour garantir que le test passe quel que soit le jour d'exécution.
    $universalSchedule = json_encode([
      [
        "startDay" => "1",
        "startTime" => "00:00",
        "endDay" => "7",
        "endTime" => "23:59"
      ]
    ]);

    $stmt = $this->pdo->prepare("
            UPDATE user_subscriptions 
            SET schedule_json = :schedule, 
                is_active = 1,
                start_date = DATE_SUB(NOW(), INTERVAL 1 DAY),
                end_date = DATE_ADD(NOW(), INTERVAL 30 DAY)
            WHERE parking_id = 'p-sub'
        ");
    $stmt->execute(['schedule' => $universalSchedule]);

    // 7. ENTRÉE DANS LE PARKING
    $res = $this->client->post("/parkings/p-sub/enter", [
      'headers' => ['Accept' => 'application/json']
    ]);

    $this->assertEquals(200, $res->getStatusCode(), "L'abonné aurait dû pouvoir entrer (Code reçu: " . $res->getStatusCode() . ")");
  }
}
