<?php

use App\Infrastructure\Repository\SqlParkingRepository;
use App\Infrastructure\Service\RamseyIdGenerator;
use App\Infrastructure\Controller\Owner\CreateParkingController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\Infrastructure\Controller\Owner\ShowCreateParkingFormController;
use App\Infrastructure\Middleware\AuthMiddleware;
use App\Infrastructure\Security\JwtService;
use App\UseCase\Owner\CreateParking\CreateParking;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use App\UseCase\Owner\UpdateParkingPrice\UpdateParkingPrice;
use App\UseCase\Owner\UpdateParkingHours\UpdateParkingHours;
use App\UseCase\Owner\AddParkingSubscriptionPlan\AddParkingSubscriptionPlan;

$c = [];

// --- 1. Base de données ---
$c[PDO::class] = function () {
  $host = getenv('MYSQL_HOST') ?: 'mysql';
  $db   = getenv('MYSQL_DATABASE') ?: 'parking_db';
  $user = getenv('MYSQL_USER') ?: 'user';
  $pass = getenv('MYSQL_PASSWORD') ?: 'password';
  return new PDO("mysql:host=$host;dbname=$db", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);
};

// --- 2. Services Infra ---
$c[SqlParkingRepository::class] = fn($c) => new SqlParkingRepository($c[PDO::class]());
$c[RamseyIdGenerator::class]    = fn() => new RamseyIdGenerator();
$c[PresenterFactory::class] = fn($c) => new PresenterFactory($c[Environment::class]($c));

// --- 3. SÉCURITÉ (LA CORRECTION EST ICI) ---

// On crée une fausse classe qui ÉTEND la vraie classe JwtService.
// Cela permet de tromper le type-hinting du Middleware.
$c[JwtService::class] = function () {
  // On passe une fausse clé au constructeur parent pour qu'il ne plante pas
  return new class('fake_secret_key') extends JwtService {

    // On surcharge la méthode decodeToken pour simuler notre propriétaire
    public function decodeToken(string $token): object
    {
      if ($token === 'TOKEN_PROPRIO_TEST') {
        // On retourne l'objet attendu par le code de ton collègue
        return (object) [
          'id' => 'owner-1',
          'role' => 'OWNER',
          'email' => 'test@owner.com'
        ];
      }

      // Si ce n'est pas le token de test, on lance une erreur
      throw new \Exception("Token de test invalide (Mock)");
    }


    // (Optionnel) On peut aussi mocker generateToken si besoin
    public function generateToken($account): string
    {
      return "fake_token";
    }
  };
};

// On injecte ce faux service dans le middleware
$c[AuthMiddleware::class] = fn($c) => new AuthMiddleware($c[JwtService::class]());


// --- 4. Use Cases ---
$c[CreateParking::class] = fn($c) => new CreateParking(
  $c[SqlParkingRepository::class]($c),
  $c[RamseyIdGenerator::class]()
);

// (J'ai ajouté les autres Use Cases pour que ton OwnerController complet fonctionne plus tard)
// Tu peux les commenter si tu ne les as pas encore créés
/*
$c[UpdateParkingPrice::class] = fn($c) => new UpdateParkingPrice($c[SqlParkingRepository::class]());
$c[UpdateParkingHours::class] = fn($c) => new UpdateParkingHours($c[SqlParkingRepository::class]());
$c[AddParkingSubscriptionPlan::class] = fn($c) => new AddParkingSubscriptionPlan($c[SqlParkingRepository::class]());
*/

// --- 5. Controllers ---
$c[CreateParkingController::class] = function ($c) {
  return new CreateParkingController(
    $c[CreateParking::class]($c),    // On repasse $c ici aussi !
    $c[PresenterFactory::class]($c)  // Et ici !
  );
};
$c[ShowCreateParkingFormController::class] = fn($c) => new ShowCreateParkingFormController(
  $c[Environment::class]($c)
);
// Configuration de Twig
$c[Environment::class] = function ($c) {
  // 1. Charger Twig
  $loader = new FilesystemLoader(__DIR__ . '/../templates');
  $twig = new Environment($loader, [
    'cache' => false,
    'debug' => true,
  ]);

  // 2. RECUPERER L'UTILISATEUR DEPUIS LE COOKIE (Si présent)
  $user = null;
  if (isset($_COOKIE['auth_token'])) {
    try {
      // On utilise ton JwtService pour décoder le token du cookie
      $jwtService = $c[JwtService::class](); // On récupère l'instance
      $user = $jwtService->decodeToken($_COOKIE['auth_token']);

      // On transforme l'objet en tableau pour Twig (plus simple)
      $user = (array) $user;
    } catch (\Exception $e) {
      // Si le token est invalide/expiré, on ignore (user reste null)
    }
  }

  // 3. INJECTER LA VARIABLE GLOBALE 'user'
  // Désormais, {{ user }} est disponible dans TOUS les templates
  $twig->addGlobal('user', $user);

  return $twig;
};
return $c;
