<?php

declare(strict_types=1);

// Chargement de l'autoloader et des dépendances
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
use App\Infrastructure\Controller\Auth\ShowRegisterUserController;
use App\Infrastructure\Controller\Auth\RegisterUserController;
use App\Infrastructure\Controller\User\SearchParkingsController;
use App\Infrastructure\Controller\User\ShowReservationFormController;
use App\Infrastructure\Controller\User\CreateReservationController;
use App\Infrastructure\Controller\Shared\GetParkingDetailsController;
use App\Infrastructure\Controller\User\EnterParkingController;
use App\Infrastructure\Controller\User\ExitParkingController;
use App\Infrastructure\Controller\Owner\ShowAddParkingSubscriptionPlanController;
use App\Infrastructure\Controller\Owner\AddParkingSubscriptionPlanController;
use App\Infrastructure\Controller\Owner\ShowUpdateParkingPriceFormController;
use App\Infrastructure\Controller\User\SubscribeToParkingPlanController;
use App\Infrastructure\Controller\User\GetReservationsController;
use App\Infrastructure\Controller\User\GenerateInvoiceController;
use App\Infrastructure\Controller\User\GetParkingSessionsController;
use App\Infrastructure\Controller\Owner\UpdateParkingPriceController;
use App\Infrastructure\Controller\Owner\ShowUpdateParkingHoursFormController;
use App\Infrastructure\Controller\Owner\UpdateParkingHoursController;
use App\Infrastructure\Controller\Owner\GetParkingReservationsController;
use App\Infrastructure\Controller\Owner\GetOwnerParkingSessionsController;
use App\Infrastructure\Controller\Owner\GetParkingAvailabilityController;
use App\Infrastructure\Controller\Owner\GetParkingRevenueController;
use App\Infrastructure\Controller\Owner\GetUnauthorizedParkersController;
use App\Infrastructure\Controller\Shared\HomeController;
// --- DÉBUT DU BLOC GLOBAL ---
try {

  // 1. TENTATIVE D'AUTHENTIFICATION (GLOBAL)
  try {
    $middlewareFactory = $container[AuthMiddleware::class];
    $authMiddleware = $middlewareFactory($container);
    $userPayload = $authMiddleware->authenticate();
    $_REQUEST['auth_user'] = (array) $userPayload;
  } catch (Exception $e) {
  }

  // 2. DÉFINITION DES ROUTES
  $dispatcher = simpleDispatcher(function (RouteCollector $r) {

    $r->addRoute('GET', '/', HomeController::class);
    // Owner
    $r->addRoute('GET', '/parkings/new', ShowCreateParkingFormController::class);
    $r->addRoute('POST', '/parkings', CreateParkingController::class);
    $r->addRoute('GET', '/parkings/{id}/plans/new', ShowAddParkingSubscriptionPlanController::class);
    $r->addRoute('POST', '/parkings/{id}/plans', AddParkingSubscriptionPlanController::class);
    $r->addRoute('GET', '/parkings/{id}/prices', ShowUpdateParkingPriceFormController::class);
    $r->addRoute('POST', '/parkings/{id}/prices', UpdateParkingPriceController::class);
    $r->addRoute('GET', '/parkings/{parkingId}/hours', ShowUpdateParkingHoursFormController::class);
    $r->addRoute('POST', '/parkings/{parkingId}/hours', UpdateParkingHoursController::class);
    $r->addRoute('GET', '/parkings/{id}/reservations', GetParkingReservationsController::class);
    $r->addRoute('GET', '/parkings/{id}/sessions', GetOwnerParkingSessionsController::class);
    $r->addRoute('GET', '/parkings/{id}/availability', GetParkingAvailabilityController::class);
    $r->addRoute('GET', '/parkings/{id}/revenue', GetParkingRevenueController::class);
    $r->addRoute('GET', '/parkings/{id}/unauthorized', GetUnauthorizedParkersController::class);

    // User
    $r->addRoute('GET', '/search', SearchParkingsController::class);
    $r->addRoute('GET', '/reservation/new', ShowReservationFormController::class);
    $r->addRoute('POST', '/reservation', CreateReservationController::class);
    $r->addRoute('GET', '/reservations', GetReservationsController::class);
    $r->addRoute('GET', '/reservations/{id}/invoice', GenerateInvoiceController::class);
    $r->addRoute('POST', '/parkings/{id}/enter', EnterParkingController::class);
    $r->addRoute('POST', '/parkings/{id}/exit', ExitParkingController::class);
    $r->addRoute('POST', '/parkings/{parkingId}/subscribe', SubscribeToParkingPlanController::class);
    $r->addRoute('GET', '/parkings/sessions', GetParkingSessionsController::class);

    // Shared
    $r->addRoute('GET', '/parkings/{id}', GetParkingDetailsController::class);

    // Dashboard
    $r->addRoute('GET', '/dashboard', ListOwnerParkingsController::class);
    $r->addRoute('GET', '/my-parkings', ListOwnerParkingsController::class);

    // Auth
    $r->addRoute('GET', '/register/owner', ShowRegisterOwnerController::class);
    $r->addRoute('POST', '/register/owner', RegisterOwnerController::class);
    $r->addRoute('GET', '/register/user', ShowRegisterUserController::class);
    $r->addRoute('POST', '/register/user', RegisterUserController::class);
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
      throw new Exception("La page demandée est introuvable", 404);

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
      throw new Exception("Méthode non autorisée pour cette route", 405);

    case FastRoute\Dispatcher::FOUND:
      $handler = $routeInfo[1];
      $vars = $routeInfo[2]; // ex: ['id' => 'abc-123']

      // Récupération du contrôleur
      if (isset($container[$handler])) {
        $factory = $container[$handler];
        $controller = $factory($container);
      } elseif (class_exists($handler)) {
        $controller = new $handler();
      } else {
        throw new Exception("Contrôleur introuvable : $handler", 500);
      }

      call_user_func_array($controller, $vars);
      break;
  }
} catch (Exception $e) {
  // --- GESTION DES ERREURS UNIFIÉE ---
  $code = $e->getCode();
  if (!is_int($code) || $code < 100 || $code > 599) {
    $code = 500;
  }
  http_response_code($code);

  $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

  if (str_contains($accept, 'application/json')) {
    echo json_encode([
      'status' => 'error',
      'code' => $code,
      'message' => $e->getMessage()
    ]);
  } else {
    $twigFactory = $container[Environment::class];
    $twig = $twigFactory($container);

    echo $twig->render('error.html.twig', [
      'code' => $code,
      'message' => $e->getMessage()
    ]);
  }
}
