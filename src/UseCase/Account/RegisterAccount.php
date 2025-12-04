<?php

namespace App\UseCase\Account;

use App\Domain\Entity\User;
use App\Domain\Entity\Owner;
use App\Domain\Repository\AccountRepositoryInterface;
use Exception;

class RegisterAccount
{
    private AccountRepositoryInterface $accountRepository;

    public function __construct(AccountRepositoryInterface $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    /**
     * Enregistre un nouveau compte.
     * * @param string $role Le rôle souhaité (DRIVER ou OWNER).
     * @param string $email
     * @param string $password
     * @param string $firstName
     * @param string $lastName
     * @return array Informations de l'utilisateur créé.
     * @throws Exception Si l'email est déjà utilisé.
     */
    public function execute(
        string $role,
        string $email,
        string $password,
        string $firstName,
        string $lastName
    ): array {
        if ($this->accountRepository->findByEmail($email) !== null) {
            throw new Exception("L'adresse email est déjà utilisée.");
        }
        
        $id = $this->generateUniqueId();
        
        if ($role === 'OWNER') {
            $account = new Owner($id, $email, $password, $firstName, $lastName);
        } elseif ($role === 'DRIVER') {
            $account = new User($id, $email, $password, $firstName, $lastName);
        } else {
            throw new Exception("Rôle invalide spécifié.");
        }

        $this->accountRepository->save($account);

        return [
            'user_id' => $id,
            'role' => $account->getRole(),
            'email' => $account->getEmail()
        ];
    }
    
    /**
     * Génère un identifiant unique (UUID).
     * @return string
     */
    private function generateUniqueId(): string
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
}