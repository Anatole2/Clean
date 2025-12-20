<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\GetParkingRevenue;

use App\Infrastructure\Presenter\PresenterInterface;

class JsonGetParkingRevenuePresenter implements PresenterInterface
{
  public function present($response): string
  {
    return json_encode([
      'parking_id' => $response->parking->getId(),
      'parking_name' => $response->parking->getName(),
      'period' => [
        'month' => $response->month,
        'year' => $response->year
      ],
      // On renvoie les valeurs en centimes (int) pour éviter les erreurs d'arrondi côté client
      'revenue_in_cents' => [
        'reservations' => $response->revenueReservations,
        'subscriptions' => $response->revenueSubscriptions,
        'total' => $response->totalRevenue
      ],
      // Une version formatée (float) pour l'affichage direct si besoin
      'revenue_in_euro' => [
        'reservations' => $response->revenueReservations / 100,
        'subscriptions' => $response->revenueSubscriptions / 100,
        'total' => $response->totalRevenue / 100
      ]
    ]);
  }
}
