<?php

/**
 * Переключает состояние лампы (включить/выключить).
 *
 * Логика:
 * 1. Если лампа включена в авто-режиме (`flag=0` и `status=1`) — включает сохранённые значения
 *    яркости (`levelSaved`) и температуры (`cctSaved`).
 * 2. Если лампа выключена — включает сохранённые значения (`levelSaved` и `cctSaved`).
 * 3. Если лампа включена не в авто-режиме — выключает её.
 *
 * @return void
 */

$status = (int)$this->getProperty('status');
$flag   = (int)$this->getProperty('flag');

if ($flag && $status) {
    // В авто режиме и уже включена — выключаем
    $this->callMethod('turnOff');
} else {
    // Во всех остальных случаях — включаем
    $this->callMethod('turnOn');
}