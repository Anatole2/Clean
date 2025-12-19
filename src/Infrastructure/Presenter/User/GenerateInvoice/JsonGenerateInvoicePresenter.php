<?php

namespace App\Infrastructure\Presenter\User\GenerateInvoice;

use App\Infrastructure\Presenter\PresenterInterface;

class JsonGenerateInvoicePresenter implements PresenterInterface
{
  public function present($response): string
  {
    return json_encode($response);
  }
}
