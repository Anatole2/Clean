<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

class WeeklySchedule
{
  private const MINUTES_IN_DAY = 1440;
  private const MINUTES_IN_WEEK = 10080;

  /** @var array<array{start: int, end: int}> */
  private array $slots = [];

  /** @var array Données brutes pour la persistance */
  private array $originalConfig;

  public function __construct(array $scheduleConfig)
  {
    $this->originalConfig = $scheduleConfig; // On mémorise l'entrée

    foreach ($scheduleConfig as $config) {
      // Petite sécurité pour éviter les notices PHP si le tableau est mal formé
      if (!isset($config['startDay'], $config['startTime'], $config['endDay'], $config['endTime'])) {
        continue;
      }

      $this->addRange(
        (int)$config['startDay'],
        $config['startTime'],
        (int)$config['endDay'],
        $config['endTime']
      );
    }

    usort($this->slots, fn($a, $b) => $a['start'] <=> $b['start']);
  }

  private function addRange(int $startDay, string $startTime, int $endDay, string $endTime): void
  {
    $startMin = $this->toAbsoluteMinute($startDay, $startTime);
    $endMin = $this->toAbsoluteMinute($endDay, $endTime);

    if ($startMin < $endMin) {
      $this->slots[] = ['start' => $startMin, 'end' => $endMin];
    } elseif ($endMin < $startMin) {
      $this->slots[] = ['start' => $startMin, 'end' => self::MINUTES_IN_WEEK];
      $this->slots[] = ['start' => 0, 'end' => $endMin];
    }
  }

  public function isOpen(\DateTimeImmutable $time): bool
  {
    if (empty($this->slots)) return true;

    $currentMinute = $this->toAbsoluteMinute((int)$time->format('N'), $time->format('H:i'));

    foreach ($this->slots as $slot) {
      if ($currentMinute >= $slot['start'] && $currentMinute < $slot['end']) {
        return true;
      }
    }
    return false;
  }

  private function toAbsoluteMinute(int $day, string $time): int
  {
    // 👇 CORRECTION ICI : On remet le message long attendu par le test
    if ($day < 1 || $day > 7) {
      throw new InvalidArgumentException("Le jour doit être entre 1 (Lundi) et 7 (Dimanche)");
    }

    [$h, $m] = explode(':', $time);
    return (($day - 1) * self::MINUTES_IN_DAY) + ((int)$h * 60) + (int)$m;
  }
  /**
   * Retourne la configuration brute (lisible) pour la BDD et l'API
   */
  public function toArray(): array
  {
    return $this->originalConfig;
  }
}
