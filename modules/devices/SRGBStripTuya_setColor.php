<?php
/**
 * Устанавливает цвет устройства.
 *
 * Метод принимает цвет двумя способами:
 *  - через параметр 'color';
 *  - через параметр 'value'.
 *
 * Значение цвета может быть:
 *  - шестнадцатеричным кодом формата `#RRGGBB`;
 *  - именем предустановленного цвета:
 *      'red', 'green', 'blue', 'white', 'yellow',
 *      'cyan', 'magenta', 'orange', 'purple',
 *      'pink', 'lime'.
 *
 * Если ни 'color', ни 'value' не переданы — метод ничего не делает.
 *
 * @param array $params Ассоциативный массив параметров,
 *                      содержащий ключи:
 *                      - 'color' (string) — цвет или пресет;
 *                      - 'value' (string) — альтернативное имя параметра цвета.
 *
 * @return void
 */

$color = $params['color'] ?? $params['value'] ?? null;
if ($level === null) return;

$this->setProperty('color', $color);
