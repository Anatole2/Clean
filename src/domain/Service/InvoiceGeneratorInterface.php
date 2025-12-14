<?php

namespace App\Domain\Service;

use App\Domain\Entity\Stationnement;

interface InvoiceGeneratorInterface
{
    public function generate(Stationnement $stationnement): string;
}

