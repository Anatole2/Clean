<?php

namespace App\Domain\Entity;

abstract class Account
{
    protected string $id;
    protected string $email;
    protected string $passwordHash;
    protected string $firstName;
    protected string $lastName;

    public function __construct(string $id, string $email, string $password, string $firstName, string $lastName)
    {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $this->hashPassword($password);
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT); 
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    protected function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    abstract public function getRole(): string;
}

?>