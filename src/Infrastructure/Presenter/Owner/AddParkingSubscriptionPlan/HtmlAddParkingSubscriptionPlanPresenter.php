<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\Owner\AddParkingSubscriptionPlan;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\Owner\AddParkingSubscriptionPlan\AddParkingSubscriptionPlanResponse;
use Twig\Environment;

class HtmlAddParkingSubscriptionPlanPresenter implements PresenterInterface
{
  public function __construct(
    private Environment $twig
  ) {}

  public function present(object $responseDTO): string
  {
    // Au lieu de rediriger, on rend une vue de confirmation
    return $this->twig->render('owner/add_parking_subscription_plan_success.html.twig', [
      'parkingId' => $responseDTO->parkingId,
      'plans' => $responseDTO->subscriptionPlans
    ]);
  }
}
