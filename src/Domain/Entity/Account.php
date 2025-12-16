<?php

declare(strict_types=1);

namespace App\Domain\Entity;

abstract class Account
{
  public function __construct(
    protected string $id,
    protected string $email,
    protected string $passwordHash,
    protected string $firstName,
    protected string $lastName
  ) {}

  /**
   * Factory pour créer un NOUVEL utilisateur (Register).
   * C'est SEULEMENT ici qu'on hache le mot de passe.
   */
  public static function create(string $id, string $email, string $plainPassword, string $firstName, string $lastName): static
  {
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    return new static($id, $email, $hash, $firstName, $lastName);
  }

  /**
   * Factory pour reconstruire depuis la BDD (Login/Fetch).
   * Ici, on passe le hash existant, donc c'est instantané.
   */
  public static function reconstitute(string $id, string $email, string $passwordHash, string $firstName, string $lastName): static
  {
    return new static($id, $email, $passwordHash, $firstName, $lastName);
  }

  // --- Logique Métier ---

  public function verifyPassword(string $plainPassword): bool
  {
    return password_verify($plainPassword, $this->passwordHash);
  }

  abstract public function getRole(): string;

  // --- Getters ---

  public function getId(): string
  {
    return $this->id;
  }

  public function getEmail(): string
  {
    return $this->email;
  }

  public function getPasswordHash(): string
  {
    return $this->passwordHash;
  }

  public function getFirstName(): string
  {
    return $this->firstName;
  }

  public function getLastName(): string
  {
    return $this->lastName;
  }
}
