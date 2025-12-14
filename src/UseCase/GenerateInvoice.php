<?php

namespace App\UseCase;

use App\Domain\Repository\StationnementRepositoryInterface;
use App\Domain\Service\InvoiceGeneratorInterface;

class GenerateInvoice
{
    public function __construct(
        private StationnementRepositoryInterface $stationnementRepo,
        private InvoiceGeneratorInterface $invoiceGenerator
    ) {}

    public function execute(string $userId, int $stationnementId): string
    {
       
        $stationnement = $this->stationnementRepo->findById($stationnementId);

        if (!$stationnement) {
            throw new \Exception("Facture introuvable.");
        }

      
        $stationnementUser = $stationnement->getUser();
        
        if ($stationnement->isCurrentlyParked()) {
            throw new \Exception("Impossible de générer une facture pour un stationnement en cours.");
        }

        return $this->invoiceGenerator->generate($stationnement);
    }
}

