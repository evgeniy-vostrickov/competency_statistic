<?php
namespace report_competency_statistic\output;

abstract class report_general {
  /** @var string $date_start */
  protected $date_start;
  /** @var string $date_end */
  protected $date_end;
  /** @var string $date_start_stmp */
  protected $date_start_stmp;
  /** @var string $date_end_stmp */
  protected $date_end_stmp;
  protected $GRAY_COLOR = '#95a5a6';
  protected $NOT_COMPETENCIES = 'Нет освоенных компетенций';
  protected $NOT_DONE = 'Не выполнено';

  protected function generateRandomColors($count)
  {
    $colors = array();
  
    for ($i = 0; $i < $count; $i++) {
      // Генерируем случайные значения для каждого канала RGB
      $r = mt_rand(0, 255);
      $g = mt_rand(0, 255);
      $b = mt_rand(0, 255);
  
      // Формируем строку в формате "#rrggbb"
      $color = sprintf("#%02x%02x%02x", $r, $g, $b);
  
      // Добавляем цвет в массив
      $colors[] = $color;
    }
  
    return $colors;
  }
  
  // Добавлена функция работы с периодом
  protected function is_in_period($object): bool {
      if ($this->date_end_stmp == null || $this->date_start_stmp == null) return true;
      return $object->get("timecreated") < $this->date_end_stmp && $object->get("timecreated") > $this->date_start_stmp;
  }
}
