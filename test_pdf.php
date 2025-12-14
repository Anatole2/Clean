<?php

require 'vendor/autoload.php';

use App\Infrastructure\Service\DompdfInvoiceGenerator;
use App\Domain\Entity\Stationnement;
use App\Domain\Entity\User;
use App\Domain\Entity\Parking;
use App\Domain\ValueObject\Money;


$user = new User();
$parking = new Parking();
$entry = new DateTimeImmutable('2023-10-10 10:00:00');
$exit = new DateTimeImmutable('2023-10-10 12:00:00');
$stationnement = new Stationnement($user, $parking, $entry, $exit, 12345);
$stationnement->markAsExited($exit, new Money(125.00)); 

$generator = new DompdfInvoiceGenerator();
$pdfContent = $generator->generate($stationnement);


file_put_contents('ma_facture_test.pdf', $pdfContent);

echo "PDF généré ! Ouvre le fichier 'ma_facture_test.pdf' pour vérifier.\n";

