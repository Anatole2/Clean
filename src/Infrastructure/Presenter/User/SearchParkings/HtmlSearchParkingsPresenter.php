<?php

declare(strict_types=1);

namespace App\Infrastructure\Presenter\User\SearchParkings;

use App\Infrastructure\Presenter\PresenterInterface;
use App\UseCase\User\SearchParkings\SearchParkingsResponse;
use Twig\Environment;

class HtmlSearchParkingsPresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}

  public function present(object $response): string
  {
    /** @var SearchParkingsResponse $response */

    return $this->twig->render('user/search_parkings_results.html.twig', [
      'parkings' => $response->parkings
    ]);
  }
}
