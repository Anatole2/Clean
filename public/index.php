<?php

declare(strict_types=1);

// On charge l'autoloader et les dépendances
require __DIR__ . '/../vendor/autoload.php';
$container = require __DIR__ . '/../config/dependencies.php';

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Infrastructure\Middleware\AuthMiddleware;
use Twig\Environment;
use App\Infrastructure\Controller\Owner\ShowCreateParkingFormController;
use App\Infrastructure\Controller\Owner\CreateParkingController;
use App\Infrastructure\Controller\Owner\ListOwnerParkingsController;
use App\Infrastructure\Controller\Auth\LoginController;
use App\Infrastructure\Controller\Auth\LogoutController;
use App\Infrastructure\Controller\Auth\ShowRegisterOwnerController;
use App\Infrastructure\Controller\Auth\RegisterOwnerController;
// --- DÉBUT DU BLOC GLOBAL ---
// On met TOUT le code logique dans ce try. 
// S'il y a la moindre erreur (Auth, Route pas trouvée, Controller qui plante...), on va dans le catch.
try {

  // 1. TENTATIVE D'AUTHENTIFICATION (GLOBAL)
  // On essaie d'identifier l'user, mais on ne bloque pas TOUT DE SUITE si ça échoue.
  // On veut permettre l'affichage des pages publiques (comme le login).
  try {
    $middlewareFactory = $container[AuthMiddleware::class];
    $authMiddleware = $middlewareFactory($container);
    $userPayload = $authMiddleware->authenticate();
    $_REQUEST['auth_user'] = (array) $userPayload;
  } catch (Exception $e) {
    // On ne fait rien ici. L'utilisateur est juste "non connecté".
    // C'est le Contrôleur plus loin qui décidera si c'est grave ou pas via ensureIsOwner().
  }

  // 2. DÉFINITION DES ROUTES
  $dispatcher = simpleDispatcher(function (RouteCollector $r) {
    // Tes routes
    // Parkings
    $r->addRoute('GET', '/parkings/new', ShowCreateParkingFormController::class);
    $r->addRoute('POST', '/parkings', CreateParkingController::class);
    // Ajoute ici tes futures routes (Dashboard, Login, etc.)

    // Liste owner parkings (dashboard and json)
    $r->addRoute('GET', '/dashboard', ListOwnerParkingsController::class);
    $r->addRoute('GET', '/my-parkings', ListOwnerParkingsController::class);

    // Register Owner
    $r->addRoute('GET', '/register/owner', ShowRegisterOwnerController::class);
    $r->addRoute('POST', '/register/owner', RegisterOwnerController::class);

    // Login & Logout
    $r->addRoute(['GET', 'POST'], '/login', LoginController::class);
    $r->addRoute('GET', '/logout', LogoutController::class);
  });

  // 3. ANALYSE DE L'URL (DISPATCH)
  $httpMethod = $_SERVER['REQUEST_METHOD'];
  $uri = $_SERVER['REQUEST_URI'];
  if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
  }
  $uri = rawurldecode($uri);

  $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

  // 4. EXÉCUTION DE LA ROUTE
  switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
      // On lance une exception pour profiter de la page d'erreur Twig en bas
      throw new Exception("La page demandée est introuvable", 404);

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
      throw new Exception("Méthode non autorisée pour cette route", 405);

    case FastRoute\Dispatcher::FOUND:
      $handler = $routeInfo[1];
      $vars = $routeInfo[2];

      // On vérifie et récupère le contrôleur depuis le container
      if (isset($container[$handler])) {
        $factory = $container[$handler];
        $controller = $factory($container); // Injection de dépendances
      } elseif (class_exists($handler)) {
        $controller = new $handler();
      } else {
        throw new Exception("Contrôleur introuvable : $handler", 500);
      }

      // On exécute le contrôleur. S'il lance "ensureIsOwner" (Exception),
      // elle sera attrapée par le catch tout en bas !
      $controller($vars);
      break;
  }
} catch (Exception $e) {
  // --- GESTION DES ERREURS UNIFIÉE ---

  // 1. Code HTTP
  $code = $e->getCode();
  if (!is_int($code) || $code < 100 || $code > 599) {
    $code = 500;
  }
  http_response_code($code);

  // 2. Détection du format (JSON vs HTML)
  $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

  if (str_contains($accept, 'application/json')) {
    // API (Postman, cURL...)
    echo json_encode([
      'status' => 'error',
      'code' => $code,
      'message' => $e->getMessage()
    ]);
  } else {
    // NAVIGATEUR (Chrome, Firefox...)

    // Optionnel : Si c'est une erreur 403/Auth, on pourrait rediriger vers /login
    /*
        if ($code === 403 || $e->getMessage() === "Utilisateur non authentifié") {
             // header('Location: /login'); exit; 
        }
        */

    // Affichage via Twig
    // Note: On passe $container car la définition de Twig l'attend
    $twigFactory = $container[Environment::class];
    $twig = $twigFactory($container);

    echo $twig->render('error.html.twig', [
      'code' => $code,
      'message' => $e->getMessage()
    ]);
  }
}
