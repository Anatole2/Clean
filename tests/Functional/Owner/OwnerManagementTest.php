<?php

declare(strict_types=1);

namespace Tests\Functional\Owner;

use Tests\Functional\FunctionalTestCase;

class OwnerManagementTest extends FunctionalTestCase
{
  public function testOwnerCanUpdateParkingPrice(): void
  {
    // ==========================================
    // 1. ACTEUR : PROPRIÉTAIRE (OWNER)
    // ==========================================

    // Inscription & Login Owner
    $this->client->post('/register/owner', [
      'json' => [
        'email' => 'boss@test.com',
        'password' => 'password',
        'firstName' => 'Hugo',
        'lastName' => 'Boss',
        'companyName' => 'BossPark',
        'role' => 'OWNER'
      ],
      'headers' => ['Accept' => 'application/json']
    ]);

    $this->client->post('/login', [
      'json' => ['email' => 'boss@test.com', 'password' => 'password'],
      'headers' => ['Accept' => 'application/json']
    ]);

    // Création Parking (Prix initial : 2€)
    $this->client->post('/parkings', [
      'json' => [
        'name' => 'Parking Modif',
        'latitude' => 48.0,
        'longitude' => 2.0,
        'totalPlaces' => 50,
        'priceGridConfig' => ['60' => 200],
        'openingHoursConfig' => []
      ],
      'headers' => ['Accept' => 'application/json']
    ]);

    // Récupération de l'ID via le dashboard Owner
    $res = $this->client->get('/dashboard', ['headers' => ['Accept' => 'application/json']]);
    $json = json_decode($res->getBody()->getContents(), true);

    $list = $json['parkings'] ?? $json['data'] ?? $json;
    $parkingId = $list[0]['id'];

    // MODIFICATION DU PRIX (Passage à 5€ / 500 centimes)
    $res = $this->client->post("/parkings/$parkingId/prices", [
      'json' => [
        'priceGridConfig' => ['60' => 500]
      ],
      'headers' => ['Accept' => 'application/json']
    ]);

    $this->assertEquals(200, $res->getStatusCode(), "Mise à jour prix échouée");

    // ==========================================
    // 2. CHANGEMENT DE RÔLE : UTILISATEUR (USER)
    // ==========================================
    // Le propriétaire ne peut pas voir la fiche détail "User", 
    // donc on se connecte avec un compte conducteur pour vérifier.

    // Inscription User témoin
    $this->client->post('/register/user', [
      'json' => ['email' => 'client@test.com', 'password' => 'pass', 'firstName' => 'Jean', 'lastName' => 'Client', 'role' => 'USER']
    ]);

    // Login User (Cela écrase le token précédent du Owner)
    $this->client->post('/login', [
      'json' => ['email' => 'client@test.com', 'password' => 'pass']
    ]);

    // ==========================================
    // 3. VÉRIFICATION
    // ==========================================

    // Maintenant qu'on est USER, on a le droit de voir les détails
    $res = $this->client->get("/parkings/$parkingId", [
      'headers' => ['Accept' => 'application/json']
    ]);

    $this->assertEquals(200, $res->getStatusCode(), "Impossible d'accéder aux détails en tant que User");

    $details = json_decode($res->getBody()->getContents(), true);

    // Gestion robuste des clés (snake_case vs camelCase)
    $grid = $details['priceGrid'] ?? $details['price_grid'] ?? [];

    // On cherche le prix pour 60 minutes
    $price = $grid['60'] ?? $grid[60] ?? 0;

    $this->assertEquals(500, $price, "Le prix pour 60min devrait être 500 (mis à jour par l'owner)");
  }
}
