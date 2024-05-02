<?php
namespace report_competency_statistic\output;

abstract class report_general {
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
}
