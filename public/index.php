<?php

require __DIR__ . '/../vendor/autoload.php';

$container = require __DIR__ . '/../config/dependencies.php';

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Infrastructure\Middleware\AuthMiddleware;
use App\Infrastructure\Controller\Owner\CreateParkingController;

// --- 1. GESTION DU MIDDLEWARE D'AUTHENTIFICATION ---
try {
  // On récupère la factory (la fonction)
  $middlewareFactory = $container[AuthMiddleware::class];

  // On EXÉCUTE la factory en lui passant le container pour créer l'objet
  /** @var AuthMiddleware $authMiddleware */
  $authMiddleware = $middlewareFactory($container);
  // Si ça marche, ça renvoie un objet User
  $userPayload = $authMiddleware->authenticate();

  // On le stocke pour plus tard
  $_REQUEST['auth_user'] = (array) $userPayload;
} catch (Exception $e) {
  // Si pas de token ou invalide : on continue (l'utilisateur est invité)
  // Le contrôleur bloquera si besoin.
}

// --- 2. ROUTAGE ---
$dispatcher = simpleDispatcher(function (RouteCollector $r) {
  $r->addRoute('POST', '/parkings', CreateParkingController::class); // Reçoit les données et créer un parking
});

// --- 3. DISPATCH ---
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
if (false !== $pos = strpos($uri, '?')) $uri = substr($uri, 0, $pos);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
  case FastRoute\Dispatcher::NOT_FOUND:
    http_response_code(404);
    echo json_encode(['error' => 'Route introuvable']);
    break;
  case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non permise']);
    break;
  case FastRoute\Dispatcher::FOUND:
    $handler = $routeInfo[1]; // Ex: App\Infrastructure\Controller\Owner\CreateParkingController
    $vars = $routeInfo[2];

    // 1. On vérifie si la classe est dans le container
    if (isset($container[$handler])) {
      $factory = $container[$handler];

      // 2. On exécute la factory en lui passant le container ($container)
      // C'est ici que l'erreur "Too few arguments" se produisait
      $controller = $factory($container);

      // 3. On lance le contrôleur (Invoke)
      $controller();
    } else {
      // Fallback : Si pas dans le container, on essaie de l'instancier directement (rare)
      if (class_exists($handler)) {
        $controller = new $handler();
        $controller();
      } else {
        http_response_code(500);
        echo json_encode(['error' => "Service introuvable : $handler"]);
      }
    }
    break;
}
