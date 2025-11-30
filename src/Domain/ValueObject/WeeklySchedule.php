<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

class WeeklySchedule
{
  private const MINUTES_IN_HOUR = 60;
  private const MINUTES_IN_DAY = 1440;
  private const MINUTES_IN_WEEK = 10080; // 7 * 1440

  /** * @var array<array{start: int, end: int}> 
   * Liste d'intervalles en minutes absolues (ex: [0, 480])
   */
  private array $slots = [];

  /**
   * @param array $scheduleConfig Configuration brute (JSON)
   * Format attendu pour chaque créneau :
   * [
   * 'startDay' => 1 (Lundi) à 7 (Dimanche),
   * 'startTime' => '08:00',
   * 'endDay' => 1,
   * 'endTime' => '18:00'
   * ]
   */
  public function __construct(array $scheduleConfig)
  {
    foreach ($scheduleConfig as $config) {
      $this->addRange(
        $config['startDay'],
        $config['startTime'],
        $config['endDay'],
        $config['endTime']
      );
    }

    // Optimisation : on trie les créneaux
    usort($this->slots, fn($a, $b) => $a['start'] <=> $b['start']);
  }

  private function addRange(int $startDay, string $startTime, int $endDay, string $endTime): void
  {
    $startMin = $this->toAbsoluteMinute($startDay, $startTime);
    $endMin = $this->toAbsoluteMinute($endDay, $endTime);

    // Cas 1 : Créneau normal (ex: Lundi 08h -> Lundi 18h)
    if ($startMin < $endMin) {
      $this->slots[] = ['start' => $startMin, 'end' => $endMin];
      return;
    }

    // Cas 2 : Chevauchement de semaine (ex: Dimanche 22h -> Lundi 06h)
    // Ou long week-end (Vendredi 18h -> Lundi 08h)
    if ($endMin < $startMin) {
      // Partie 1 : De Start jusqu'à la fin de la semaine
      $this->slots[] = ['start' => $startMin, 'end' => self::MINUTES_IN_WEEK];
      // Partie 2 : Du début de la semaine (0) jusqu'à End
      $this->slots[] = ['start' => 0, 'end' => $endMin];
    }
  }

  public function isOpen(\DateTimeImmutable $time): bool
  {
    // Si aucune config = ouvert tout le temps (ou fermé selon règle métier)
    if (empty($this->slots)) {
      return true;
    }

    $currentMinute = $this->toAbsoluteMinute(
      (int)$time->format('N'),
      $time->format('H:i')
    );

    foreach ($this->slots as $slot) {
      // Est-ce que la minute actuelle est dans l'intervalle ?
      // On utilise >= pour le début et < pour la fin (exclusif)
      if ($currentMinute >= $slot['start'] && $currentMinute < $slot['end']) {
        return true;
      }
    }

    return false;
  }

  /**
   * Convertit un Jour + Heure en minute de la semaine (0 à 10079)
   */
  private function toAbsoluteMinute(int $day, string $time): int
  {
    if ($day < 1 || $day > 7) {
      throw new InvalidArgumentException("Le jour doit être entre 1 (Lundi) et 7 (Dimanche).");
    }

    [$hours, $minutes] = explode(':', $time);

    return (($day - 1) * self::MINUTES_IN_DAY) + ((int)$hours * 60) + (int)$minutes;
  }

  public function toArray(): array
  {
    return $this->slots;
  }
}
