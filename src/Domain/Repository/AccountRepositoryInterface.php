<?php

namespace App\Domain\Repository;

use App\Domain\Entity\Account;

/**
 * Interface définissant les opérations de persistance pour les entités Account.
 */
interface AccountRepositoryInterface
{
    /**
     * Sauvegarde ou met à jour un compte.
     * @param Account $account L'entité à sauvegarder.
     * @return void
     */
    public function save(Account $account): void;

    /**
     * Recherche un compte par son adresse email.
     * @param string $email L'adresse email.
     * @return Account|null L'entité Account trouvée, ou null.
     */
    public function findByEmail(string $email): ?Account;
}