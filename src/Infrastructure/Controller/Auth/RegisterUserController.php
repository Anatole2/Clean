<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Auth;

use App\Infrastructure\Controller\AbstractController;
use App\Infrastructure\Presenter\PresenterFactory;
use App\UseCase\Auth\RegisterUser\RegisterUser;
use App\UseCase\Auth\RegisterUser\RegisterUserRequest;
use Twig\Environment;

class RegisterUserController extends AbstractController
{
  public function __construct(
    private RegisterUser $useCase,
    private PresenterFactory $presenterFactory,
    private Environment $twig
  ) {}

  public function __invoke(): void
  {
    $input = $this->getRequestData();


    try {
      $this->validateInputs($input);

      $request = new RegisterUserRequest(
        $input['email'],
        $input['password'],
        $input['firstName'],
        $input['lastName']
      );

      $response = $this->useCase->execute($request);

      $presenter = $this->presenterFactory->create('Auth\\RegisterUser');
      echo $presenter->present($response);
    } catch (\Exception $e) {
      $this->handleError($e, $input);
    }
  }

  /**
   * Vérifie que tous les champs requis sont présents et non vides.
   * @throws \InvalidArgumentException
   */
  private function validateInputs(array $input): void
  {
    $requiredFields = [
      'firstName' => 'Prénom',
      'lastName'  => 'Nom',
      'email'     => 'Email',
      'password'  => 'Mot de passe'
    ];

    foreach ($requiredFields as $field => $label) {
      if (!isset($input[$field])) {
        throw new \InvalidArgumentException("Le champ '$label' est manquant.");
      }

      if (trim((string)$input[$field]) === '') {
        throw new \InvalidArgumentException("Le champ '$label' ne peut pas être vide.");
      }
    }

    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
      throw new \InvalidArgumentException("L'adresse email n'est pas valide.");
    }
  }

  private function handleError(\Exception $e, array $data): void
  {
    $statusCode = ($e instanceof \InvalidArgumentException) ? 400 : 500;

    if ($this->wantsJson()) {
      http_response_code($statusCode);
      echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
      return;
    }

    echo $this->twig->render('auth/register_user.html.twig', [
      'error' => $e->getMessage(),
      'last_email' => $data['email'] ?? '',
      'last_firstname' => $data['firstName'] ?? '',
      'last_lastname' => $data['lastName'] ?? ''
    ]);
  }
}
