<?php
/**
 * Выключает лампу и сбрасывает флаги авто-режима.
 *
 * Данный метод:
 *   - переводит свойство `status` в 0,
 *   - сбрасывает `flag` (остановка авто-режима и авто-выключения),
 *   - сбрасывает `illuminanceFlag` (блокировка работы по датчику освещённости).
 *
 * @return void
 */

$this->setProperty('status', 0);
$this->setProperty('flag', 0);
$this->setProperty('illuminanceFlag', 0);
