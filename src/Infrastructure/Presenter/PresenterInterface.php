<?php

namespace App\Infrastructure\Presenter;

interface PresenterInterface
{
  /**
   * Transforme un DTO de réponse en string affichable
   */
  public function present(object $responseDTO): string;
}
