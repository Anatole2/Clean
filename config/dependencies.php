<?php
// Configuration globale du timezone pour l'application
date_default_timezone_set('Europe/Paris');

// Repositories
use App\Infrastructure\Repository\SqlParkingRepository;
use App\Infrastructure\Repository\SqlAccountRepository;
use App\Infrastructure\Repository\SqlReservationRepository;
use App\Infrastructure\Repository\SqlUserSubscriptionRepository;
use App\Domain\Repository\ParkingRepositoryInterface;
use App\Domain\Repository\AccountRepositoryInterface;
use App\Domain\Repository\ReservationRepositoryInterface;
use App\Domain\Repository\UserSubscriptionRepositoryInterface;
use App\Domain\Repository\ParkingSessionRepositoryInterface;
use App\Infrastructure\Repository\SqlParkingSessionRepository;

use App\Infrastructure\Service\RamseyIdGenerator;
use App\Infrastructure\Presenter\PresenterFactory;
use App\Infrastructure\Middleware\AuthMiddleware;
use App\Infrastructure\Security\JwtService;

// Use Cases
use App\UseCase\Owner\CreateParking\CreateParking;
use App\UseCase\Owner\GetOwnerParkings\GetOwnerParkings;
use App\UseCase\Auth\RegisterOwner\RegisterOwner;
use App\UseCase\Auth\RegisterUser\RegisterUser;
use App\UseCase\Auth\Login\Login;
use App\UseCase\User\SearchParkings\SearchParkings;
use App\UseCase\User\CreateReservation\CreateReservation;
use App\UseCase\Shared\GetParkingDetails\GetParkingDetails;
use App\UseCase\User\EnterParking\EnterParking;
use App\UseCase\User\ExitParking\ExitParking;

// Controllers
use App\Infrastructure\Controller\Owner\CreateParkingController;
use App\Infrastructure\Controller\Owner\ShowCreateParkingFormController;
use App\Infrastructure\Controller\Owner\ListOwnerParkingsController;
use App\Infrastructure\Controller\Auth\ShowRegisterOwnerController;
use App\Infrastructure\Controller\Auth\RegisterOwnerController;
use App\Infrastructure\Controller\Auth\ShowRegisterUserController;
use App\Infrastructure\Controller\Auth\RegisterUserController;
use App\Infrastructure\Controller\Auth\LoginController;
use App\Infrastructure\Controller\Auth\LogoutController;
use App\Infrastructure\Controller\User\SearchParkingsController;
use App\Infrastructure\Controller\User\ShowReservationFormController;
use App\Infrastructure\Controller\User\CreateReservationController;
use App\Infrastructure\Controller\Shared\GetParkingDetailsController;
use App\Infrastructure\Controller\User\EnterParkingController;
use App\Infrastructure\Controller\User\ExitParkingController;

// Twig
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

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

// --- 2. Repositories (MAPPING INTERFACE => IMPLEMENTATION) ---
$c[ParkingRepositoryInterface::class] = fn($c) => new SqlParkingRepository($c[PDO::class]());
$c[AccountRepositoryInterface::class] = fn($c) => new SqlAccountRepository($c[PDO::class]());
$c[ReservationRepositoryInterface::class] = fn($c) => new SqlReservationRepository($c[PDO::class]());
$c[UserSubscriptionRepositoryInterface::class] = fn($c) => new SqlUserSubscriptionRepository($c[PDO::class]());
$c[ParkingSessionRepositoryInterface::class] = fn($c) => new SqlParkingSessionRepository($c[PDO::class]());

// --- 3. Services Infra ---
$c[RamseyIdGenerator::class]    = fn() => new RamseyIdGenerator();
$c[PresenterFactory::class] = fn($c) => new PresenterFactory($c[Environment::class]($c));

// --- 4. SÉCURITÉ ---
$c[JwtService::class] = function () {
  $secretKey = getenv('JWT_SECRET');
  if ($secretKey === false || trim($secretKey) === '') {
    throw new \RuntimeException('ERREUR CRITIQUE : JWT_SECRET manquant.');
  }
  return new JwtService($secretKey);
};

$c[AuthMiddleware::class] = fn($c) => new AuthMiddleware($c[JwtService::class]());


// --- 5. Use Cases (Tout le monde utilise les Interfaces maintenant) ---
$c[CreateParking::class] = fn($c) => new CreateParking(
  $c[ParkingRepositoryInterface::class]($c),
  $c[RamseyIdGenerator::class]()
);

$c[CreateReservation::class] = fn($c) => new CreateReservation(
  $c[ParkingRepositoryInterface::class]($c),
  $c[ReservationRepositoryInterface::class]($c),
  $c[UserSubscriptionRepositoryInterface::class]($c),
  $c[RamseyIdGenerator::class]()
);

$c[GetOwnerParkings::class] = fn($c) => new GetOwnerParkings(
  $c[ParkingRepositoryInterface::class]($c)
);

$c[RegisterOwner::class] = fn($c) => new RegisterOwner(
  $c[AccountRepositoryInterface::class]($c),
  $c[RamseyIdGenerator::class]()
);

$c[RegisterUser::class] = fn($c) => new RegisterUser(
  $c[AccountRepositoryInterface::class]($c),
  $c[RamseyIdGenerator::class]()
);

$c[Login::class] = fn($c) => new Login(
  $c[AccountRepositoryInterface::class]($c),
  $c[JwtService::class]()
);

$c[SearchParkings::class] = fn($c) => new SearchParkings(
  $c[ParkingRepositoryInterface::class]($c)
);

$c[GetParkingDetails::class] = fn($c) => new GetParkingDetails(
  $c[ParkingRepositoryInterface::class]($c)
);

$c[EnterParking::class] = fn($c) => new EnterParking(
  $c[ParkingSessionRepositoryInterface::class]($c),
  $c[ReservationRepositoryInterface::class]($c),
  $c[UserSubscriptionRepositoryInterface::class]($c),
  $c[RamseyIdGenerator::class]()
);

$c[ExitParking::class] = fn($c) => new ExitParking(
  $c[ParkingSessionRepositoryInterface::class]($c),
  $c[ParkingRepositoryInterface::class]($c),
  $c[ReservationRepositoryInterface::class]($c),
  $c[UserSubscriptionRepositoryInterface::class]($c)
);

// --- 6. Controllers ---
$c[CreateParkingController::class] = function ($c) {
  return new CreateParkingController(
    $c[CreateParking::class]($c),
    $c[PresenterFactory::class]($c)
  );
};

$c[ShowCreateParkingFormController::class] = fn($c) => new ShowCreateParkingFormController(
  $c[Environment::class]($c)
);

$c[ListOwnerParkingsController::class] = fn($c) => new ListOwnerParkingsController(
  $c[GetOwnerParkings::class]($c),
  $c[PresenterFactory::class]($c)
);

$c[ShowRegisterOwnerController::class] = fn($c) => new ShowRegisterOwnerController(
  $c[Environment::class]($c)
);

$c[RegisterOwnerController::class] = fn($c) => new RegisterOwnerController(
  $c[RegisterOwner::class]($c),
  $c[PresenterFactory::class]($c),
  $c[Environment::class]($c)
);

$c[ShowRegisterUserController::class] = fn($c) => new ShowRegisterUserController(
  $c[Environment::class]($c)
);

$c[RegisterUserController::class] = fn($c) => new RegisterUserController(
  $c[RegisterUser::class]($c),
  $c[PresenterFactory::class]($c),
  $c[Environment::class]($c)
);

$c[LoginController::class] = fn($c) => new LoginController(
  $c[Login::class]($c),
  $c[Environment::class]($c)
);

$c[LogoutController::class] = fn() => new LogoutController();

$c[SearchParkingsController::class] = fn($c) => new SearchParkingsController(
  $c[SearchParkings::class]($c),
  $c[PresenterFactory::class]($c),
  $c[Environment::class]($c)
);

$c[ShowReservationFormController::class] = fn($c) => new ShowReservationFormController(
  $c[ParkingRepositoryInterface::class]($c),
  $c[Environment::class]($c)
);

$c[CreateReservationController::class] = fn($c) => new CreateReservationController(
  $c[CreateReservation::class]($c),
  $c[PresenterFactory::class]($c)
);

$c[GetParkingDetailsController::class] = fn($c) => new GetParkingDetailsController(
  $c[GetParkingDetails::class]($c),
  $c[PresenterFactory::class]($c),
  $c[Environment::class]($c)
);

$c[EnterParkingController::class] = fn($c) => new EnterParkingController(
  $c[EnterParking::class]($c)
);

$c[ExitParkingController::class] = fn($c) => new ExitParkingController(
  $c[ExitParking::class]($c)
);

// --- 7. Configuration de Twig ---
$c[Environment::class] = function ($c) {
  $loader = new FilesystemLoader(__DIR__ . '/../templates');
  $twig = new Environment($loader, [
    'cache' => false,
    'debug' => true,
  ]);

  // Récupération User via Cookie
  $user = null;
  if (isset($_COOKIE['auth_token'])) {
    try {
      $jwtService = $c[JwtService::class]();
      $user = $jwtService->decodeToken($_COOKIE['auth_token']);
      $user = (array) $user;
    } catch (\Exception $e) {
    }
  }

  $twig->addGlobal('user', $user);
  return $twig;
};

return $c;
