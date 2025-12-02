<?php

namespace Tests\Unit\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use App\Domain\ValueObject\WeeklySchedule;
use DateTimeImmutable;

class WeeklyScheduleTest extends TestCase
{
  /**
   * Scénario PDF : Uniquement le week-end du Vendredi 18h au Lundi 08h
   * 
   */
  public function testHandlesComplexWeekendRange(): void
  {
    $config = [[
      'startDay' => 5,
      'startTime' => '18:00', // Vendredi
      'endDay' => 1,
      'endTime' => '08:00'    // Lundi
    ]];

    $schedule = new WeeklySchedule($config);

    // 1. Vendredi 17:59 -> Fermé
    $fridayBefore = new DateTimeImmutable('2024-03-22 17:59:00'); // Un Vendredi
    $this->assertFalse($schedule->isOpen($fridayBefore));

    // 2. Vendredi 23:00 -> Ouvert
    $fridayNight = new DateTimeImmutable('2024-03-22 23:00:00');
    $this->assertTrue($schedule->isOpen($fridayNight));

    // 3. Samedi Midi -> Ouvert
    $saturday = new DateTimeImmutable('2024-03-23 12:00:00');
    $this->assertTrue($schedule->isOpen($saturday));

    // 4. Dimanche soir -> Ouvert (fin de semaine)
    $sunday = new DateTimeImmutable('2024-03-24 23:59:00');
    $this->assertTrue($schedule->isOpen($sunday));

    // 5. Lundi 07:59 -> Ouvert (début de semaine suivante)
    $mondayMorning = new DateTimeImmutable('2024-03-25 07:59:00');
    $this->assertTrue($schedule->isOpen($mondayMorning));

    // 6. Lundi 08:01 -> Fermé
    $mondayLate = new DateTimeImmutable('2024-03-25 08:01:00');
    $this->assertFalse($schedule->isOpen($mondayLate));
  }

  public function testHandlesMultipleSlotsInSameDay(): void
  {
    // Ouvert de 8h-12h et 14h-18h le Lundi (1)
    $config = [
      ['startDay' => 1, 'startTime' => '08:00', 'endDay' => 1, 'endTime' => '12:00'],
      ['startDay' => 1, 'startTime' => '14:00', 'endDay' => 1, 'endTime' => '18:00']
    ];

    $schedule = new WeeklySchedule($config);

    // Lundi 10h -> OK
    $this->assertTrue($schedule->isOpen(new DateTimeImmutable('Monday 10:00')));

    // Lundi 13h -> KO (Pause déj)
    $this->assertFalse($schedule->isOpen(new DateTimeImmutable('Monday 13:00')));
  }

  public function testOpen24_7IfEmptyConfig(): void
  {
    // [cite: 162] "Un parking peut être disponible en permanence"
    $schedule = new WeeklySchedule([]);
    $this->assertTrue($schedule->isOpen(new DateTimeImmutable('now')));
  }
  public function testToArrayReturnsOriginalConfig(): void
  {
    $config = [
      ['startDay' => 1, 'startTime' => '08:00', 'endDay' => 1, 'endTime' => '12:00']
    ];

    $schedule = new WeeklySchedule($config);
    $result = $schedule->toArray();

    // On ne vérifie plus 480/720, mais que l'objet nous rend ce qu'on lui a donné
    $this->assertSame($config, $result);
    $this->assertEquals('08:00', $result[0]['startTime']);
  }
  public function testThrowsExceptionForInvalidDay(): void
  {
    // On essaie de créer un planning pour le "8ème jour" de la semaine (impossible)
    $invalidConfig = [
      ['startDay' => 8, 'startTime' => '08:00', 'endDay' => 1, 'endTime' => '12:00']
    ];

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("Le jour doit être entre 1 (Lundi) et 7 (Dimanche)");

    new WeeklySchedule($invalidConfig);
  }
  public function testConstructorIgnoresMalformedConfig(): void
  {
    // 1. On prépare une config incomplète (il manque 'endTime')
    $malformedConfig = [
      ['startDay' => 1, 'startTime' => '08:00', 'endDay' => 1]
    ];

    // 2. On instancie (ne doit pas planter)
    $schedule = new WeeklySchedule($malformedConfig);

    // 3. Vérifications
    // Le toArray doit nous rendre la config brute (même cassée, c'est ce qu'on a stocké)
    $this->assertSame($malformedConfig, $schedule->toArray());

    // Comme la ligne était mal formée, elle a été ignorée par le "continue".
    // Donc aucun créneau n'a été ajouté mathématiquement.
    // Selon notre logique "si vide = ouvert tout le temps", cela devrait être ouvert.
    $this->assertTrue($schedule->isOpen(new \DateTimeImmutable('now')));
  }
}
