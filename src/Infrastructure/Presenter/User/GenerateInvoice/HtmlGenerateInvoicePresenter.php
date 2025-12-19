<?php

namespace App\Infrastructure\Presenter\User\GenerateInvoice;

use App\Infrastructure\Presenter\PresenterInterface;
use Twig\Environment;

class HtmlGenerateInvoicePresenter implements PresenterInterface
{
  public function __construct(private Environment $twig) {}

  public function present($response): string
  {
    return $this->twig->render('user/reservation_invoice.html.twig', ['invoice' => $response]);
  }
}
