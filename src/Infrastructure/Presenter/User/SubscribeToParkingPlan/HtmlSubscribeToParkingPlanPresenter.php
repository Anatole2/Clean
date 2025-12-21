<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\User\SubscribeToParkingPlan;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\User\SubscribeToParkingPlan\SubscribeToParkingPlanResponse;
use Twig\Environment;

class HtmlSubscribeToParkingPlanPresenter implements PresenterInterface
{
  public function __construct(
    private Environment $twig
  ) {}

  /**
   * @param SubscribeToParkingPlanResponse $response
   */
  public function present($response): string
  {
    return $this->twig->render('user/subscription_parking_success.html.twig', [
      'subscription' => $response->subscription
    ]);
  }
}
