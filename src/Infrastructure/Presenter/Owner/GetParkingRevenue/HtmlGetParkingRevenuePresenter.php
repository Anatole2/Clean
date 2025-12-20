<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\GetParkingRevenue;

use App\Infrastructure\Presenter\PresenterInterface;
use Twig\Environment;

class HtmlGetParkingRevenuePresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}

  public function present($response): string
  {
    return $this->twig->render('owner/parking_revenue.html.twig', [
      'parking' => $response->parking,
      'month' => $response->month,
      'year' => $response->year,
      // On divise par 100 pour passer des centimes aux euros
      'revRes' => $response->revenueReservations / 100,
      'revSub' => $response->revenueSubscriptions / 100,
      'total' => $response->totalRevenue / 100
    ]);
  }
}
