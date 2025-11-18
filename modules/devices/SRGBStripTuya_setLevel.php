<?php

/**
 * Устанавливает яркость света лампы.
 *
 * Принимает один из параметров: 'brightness', 'level' или 'value'.
 * Значение яркости должно быть в диапазоне 0–100.
 *
 * Пример вызова:
 *      callMethod('Объект.setLevel', array("value" => 0–100));
 *
 * @param array{
 *     brightness?: int|null,  // Яркость, если передана через brightness
 *     level?: int|null,       // Яркость, если передана через level
 *     value?: int|null        // Яркость, если передана через value
 * } $params Ассоциативный массив параметров.
 *
 * @return void
 */

$level = $params['brightness'] ?? $params['level'] ?? $params['value'] ?? null;
if ($level === null) return;

$this->setProperty('level', $level);
