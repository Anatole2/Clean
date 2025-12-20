<?php

declare(strict_types=1);

namespace Tests\Functional\User;

use Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;

class UserJourneyTest extends FunctionalTestCase
{
  public function testUserCanSearchReserveAndPark(): void
  {
    // 0. PRÉPARATION
    $this->pdo->exec("INSERT INTO accounts (id, email, password_hash, role) VALUES ('owner-1', 'owner@test.com', 'hash', 'OWNER')");

    $priceGrid = json_encode(['60' => 100]);
    $this->pdo->exec("INSERT INTO parkings (id, owner_id, name, latitude, longitude, total_places, price_grid, opening_hours, subscription_plans) 
            VALUES ('p1', 'owner-1', 'Parking Central', 48.85, 2.35, 10, '$priceGrid', '[]', '[]')");

    // ==========================================
    // 1. INSCRIPTION
    // ==========================================
    $res = $this->client->post('/register/user', [
      'json' => [
        'email' => 'user@test.com',
        'password' => 'password123',
        'firstName' => 'Jean',
        'lastName' => 'Dupont',
        'role' => 'USER'
      ],
      'headers' => ['Accept' => 'application/json']
    ]);

    // On accepte 200 ou 201
    $this->assertTrue(in_array($res->getStatusCode(), [200, 201]), "Échec inscription");

    // ==========================================
    // 2. LOGIN
    // ==========================================
    $res = $this->client->post('/login', [
      'json' => ['email' => 'user@test.com', 'password' => 'password123'],
      'headers' => ['Accept' => 'application/json']
    ]);
    $this->assertEquals(200, $res->getStatusCode(), "Échec login");

    // ==========================================
    // 3. RECHERCHE
    // ==========================================
    $res = $this->client->get('/search?lat=48.85&lon=2.35&radius=10', [
      'headers' => ['Accept' => 'application/json']
    ]);
    $this->assertEquals(200, $res->getStatusCode(), "Échec recherche");

    $json = json_decode($res->getBody()->getContents(), true);

    $parkings = $json['results'];

    $this->assertCount(1, $parkings, "Devrait trouver exactement 1 parking");
    $this->assertEquals('p1', $parkings[0]['id']);

    // ==========================================
    // 4. RÉSERVATION
    // ==========================================
    // On réserve pour DEMAIN pour passer la validation "Pas de réservation dans le passé"
    $start = (new DateTimeImmutable('+24 hours'))->format('Y-m-d\TH:i:s');
    $end   = (new DateTimeImmutable('+26 hours'))->format('Y-m-d\TH:i:s');

    $res = $this->client->post("/reservation", [
      'json' => [
        'parkingId'  => 'p1',
        'start_time' => $start,
        'end_time'   => $end
      ],
      'headers' => ['Accept' => 'application/json']
    ]);

    $this->assertTrue(in_array($res->getStatusCode(), [200, 201]), "Échec réservation");

    // Maintenant que la réservation est créée, on force son heure de début à "Maintenant - 10 min"
    // directement en SQL pour pouvoir tester l'entrée immédiate.
    // Cela simule le fait que l'utilisateur a attendu le lendemain.
    $this->pdo->exec("
            UPDATE reservations 
            SET start_time = DATE_SUB(NOW(), INTERVAL 10 MINUTE), 
                end_time = DATE_ADD(NOW(), INTERVAL 2 HOUR) 
            WHERE parking_id = 'p1'
        ");

    // ==========================================
    // 5. ENTRÉE
    // ==========================================
    $res = $this->client->post("/parkings/p1/enter", [
      'headers' => ['Accept' => 'application/json']
    ]);

    if ($res->getStatusCode() !== 200) {
      fwrite(STDERR, "\nERREUR ENTREE: " . $res->getBody()->getContents());
    }
    $this->assertEquals(200, $res->getStatusCode(), "Échec entrée");

    // ==========================================
    // 6. SORTIE
    // ==========================================
    // On simule un peu de temps passé ou on sort directement
    $res = $this->client->post("/parkings/p1/exit", [
      'headers' => ['Accept' => 'application/json']
    ]);
    $this->assertEquals(200, $res->getStatusCode(), "Échec sortie");
  }
}
