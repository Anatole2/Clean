<?php

namespace App\Infrastructure\Service;

use App\Domain\Service\InvoiceGeneratorInterface;
use App\Domain\Entity\Stationnement;
use Dompdf\Dompdf;
use Dompdf\Options;

class DompdfInvoiceGenerator implements InvoiceGeneratorInterface
{
    public function generate(Stationnement $stationnement): string
    {
       
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

       
        $id = $stationnement->getId() ?? '---';
        $entryDate = $stationnement->getDebutStationnement();
        $exitDate = $stationnement->getFinStationnement();
        
        $entryStr = $entryDate->format('d/m/Y à H:i');
        
     
        $durationStr = "En cours";
        $exitStr = "En cours";
        
        if ($exitDate) {
            $exitStr = $exitDate->format('d/m/Y à H:i');
            $interval = $entryDate->diff($exitDate);
        
            $durationStr = $interval->format('%d jours, %H heures, %i minutes');
        }

        $price = $stationnement->getPricePaid() 
            ? number_format($stationnement->getPricePaid()->getAmount(), 2, ',', ' ') . ' €' 
            : '0,00 €';

      
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; }
                .box { border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                .row { display: block; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
                .label { font-weight: bold; width: 150px; display: inline-block; }
                .total { font-size: 20px; font-weight: bold; text-align: right; margin-top: 30px; color: #2c3e50; }
                .footer { font-size: 10px; text-align: center; margin-top: 50px; color: #777; }
            </style>
        </head>
        <body>
            <div class='box'>
                <div class='header'>
                    <h2>REÇU DE STATIONNEMENT</h2>
                    <small>Référence : #{$id}</small>
                </div>

                <div class='content'>
                    <div class='row'>
                        <span class='label'>Début :</span> {$entryStr}
                    </div>
                    <div class='row'>
                        <span class='label'>Fin :</span> {$exitStr}
                    </div>
                    <div class='row'>
                        <span class='label'>Durée totale :</span> {$durationStr}
                    </div>
                </div>

                <div class='total'>
                    MONTANT RÉGLÉ : {$price}
                </div>
                
                <div class='footer'>
                    Merci de votre visite.<br>
                    Ce document vaut justificatif de paiement.
                </div>
            </div>
        </body>
        </html>";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'portrait'); 
        $dompdf->render();

        return $dompdf->output();
    }
}

