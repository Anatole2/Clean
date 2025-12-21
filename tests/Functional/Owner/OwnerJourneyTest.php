<?php

declare(strict_types=1);

namespace Tests\Functional\Owner;

use Tests\Functional\FunctionalTestCase;

class OwnerJourneyTest extends FunctionalTestCase
{
  public function testOwnerCanCreateAndListParking(): void
  {
    // ==========================================
    // 1. INSCRIPTION
    // ==========================================
    $res = $this->client->post('/register/owner', [
      'json' => [
        'email' => 'proprio@test.com',
        'password' => 'password123',
        'firstName' => 'Henri',
        'lastName' => 'Ford',
        'companyName' => 'Ford Parkings',
        'role' => 'OWNER'
      ],
      'headers' => ['Accept' => 'application/json']
    ]);

    $this->assertTrue(in_array($res->getStatusCode(), [200, 201]), "Échec inscription owner");

    // ==========================================
    // 2. LOGIN
    // ==========================================
    $res = $this->client->post('/login', [
      'json' => ['email' => 'proprio@test.com', 'password' => 'password123'],
      'headers' => ['Accept' => 'application/json']
    ]);
    $this->assertEquals(200, $res->getStatusCode(), "Échec login owner");

    // ==========================================
    // 3. CRÉATION D'UN PARKING
    // ==========================================

    // CORRECTION MAJEURE ICI : Structure adaptée au contrôleur
    $res = $this->client->post('/parkings', [
      'json' => [
        'name' => 'Parking Opéra',
        'latitude' => 48.87,
        'longitude' => 2.33,
        'totalPlaces' => 50,
        'priceGridConfig' => [
          '60' => 200 // 60 min => 200 centimes
        ],
        'openingHoursConfig' => [
          [
            'startDay'  => 1,      // Lundi
            'startTime' => '08:00',
            'endDay'    => 1,      // Lundi (fermeture le même jour)
            'endTime'   => '20:00'
          ]
        ]
      ],
      'headers' => ['Accept' => 'application/json']
    ]);

    if ($res->getStatusCode() !== 200 && $res->getStatusCode() !== 201) {
      fwrite(STDERR, "\nERREUR CREATE PARKING: " . $res->getBody()->getContents());
    }
    $this->assertTrue(in_array($res->getStatusCode(), [200, 201]), "Échec création parking");

    // ==========================================
    // 4. VÉRIFICATION (DASHBOARD)
    // ==========================================
    $res = $this->client->get('/my-parkings', [
      'headers' => ['Accept' => 'application/json']
    ]);
    $this->assertEquals(200, $res->getStatusCode(), "Échec récupération dashboard");

    $json = json_decode($res->getBody()->getContents(), true);

    // Tentative de récupération intelligente de la liste
    $parkings = $json['parkings'] ?? $json['data'] ?? $json['results'] ?? $json;

    // Si $parkings n'est pas un tableau (ex: erreur), on fail
    if (!is_array($parkings)) {
      fwrite(STDERR, "\nSTRUCTURE DASHBOARD REÇUE : " . print_r($json, true));
      $this->fail("La réponse du dashboard ne contient pas de liste valide.");
    }

    $this->assertGreaterThanOrEqual(1, count($parkings), "Aucun parking trouvé dans le dashboard");

    // Vérification du nom
    $found = false;
    foreach ($parkings as $p) {
      // Adaptation au cas où la clé est 'name' ou 'parkingName'
      if (($p['name'] ?? $p['parkingName'] ?? '') === 'Parking Opéra') {
        $found = true;
        break;
      }
    }
    $this->assertTrue($found, "Le parking créé n'a pas été trouvé dans la liste");
  }
}
