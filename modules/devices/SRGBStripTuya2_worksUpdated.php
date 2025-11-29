<?php
/**
 * Обрабатывает изменение свойств устройства, связанных с цветом, яркостью и сценами.
 *
 * Функция выполняет следующие задачи:
 *
 *  1. Инициализирует список сцен (scenesList), если он пуст.
 *  2. Обрабатывает изменения свойства "colorWork":
 *      - Получает новое значение HSV.
 *      - Конвертирует его в RGB Hex через hsvToRgbHex().
 *      - Устанавливает цвет (color), сохранённый цвет (colorSaved)
 *        и уровень яркости (level).
 *  3. Обрабатывает изменения свойства "sceneWork":
 *      - Получает значение выбранной сцены.
 *      - Сравнивает его со списком сцен в scenesList.
 *      - Находит имя сцены, соответствующее значению.
 *      - Устанавливает sceneName и sceneNameSaved.
 *
 * Входные параметры:
 * -------------------
 * @param array $params Ассоциативный массив, содержащий:
 *      - 'NEW_VALUE'   (mixed)  Новое значение изменённого свойства.
 *      - 'SOURCE'      (string) Источник изменения свойства.
 *      - 'PROPERTY'    (string) Имя свойства, которое изменилось.
 *
 * Важные свойства объекта:
 * ------------------------
 * - scenesList        — список доступных сцен (строка вида "Имя=Значение,...").
 * - color             — текущий HEX-цвет устройства.
 * - colorSaved        — последний сохранённый HEX-цвет.
 * - level             — уровень яркости (1–100).
 * - sceneName         — имя активной сцены.
 * - sceneNameSaved    — сохранённое имя сцены.
 *
 * Используемые функции:
 * ----------------------
 * - normalizeRange($val, $min, $max, $type) — нормализует числовое значение.
 * - hsvToRgbHex($hsv) — конвертирует HSV в массив:
 *        ['rgbHex' => string, 'brightness' => int]
 *
 * Примечания:
 * -----------
 * - Обработка не выполняется, если SOURCE == 'propertysUpdated'
 *   (во избежание рекурсии).
 * - Формат scenesList допускает разделители: запятая, \n, \r\n.
 *
 * @return void
 */

// --- Дефолтные свойства
$this->callMethod('byDefault');

$property = $params['PROPERTY'] ?? null;
$value    = $params['NEW_VALUE'] ?? null;
$source   = strtok($params['SOURCE'] ?? '', ' ');

// Защита от рекурсий. 
if ($source === 'propertysUpdated') return;

$this->setProperty('flag', 1);

//  Обработка colorWork: HSV -> RGB/Level ---
if ($property === 'colorWork' && !is_null($value)) {
    // Нормализуем значение HSV 
    $colorWorkValue = normalizeRange($value);
    // Проверка, что значение прошло валидацию (не null)
	if (is_null($colorWorkValue)) {
        return; 
    }
    // Преобразуем HSV в RGB Hex и Яркость
    $data = hsvToRgbHex($colorWorkValue);
    $colorRGB = $data['rgbHex'];
    $level = $data['brightness'];
    // Обновляем свойства.
    $this->setProperty('color', $colorRGB, 'worksUpdated');
    $this->setProperty('colorSaved', $colorRGB);
    $this->setProperty('level', $level, 'worksUpdated');
    $this->setProperty('levelSaved', $level);
    return; // Завершаем работу, если обработано colorWork
}
//  Обработка sceneWork: Код Сцены -> Имя Сцены ---
if ($property === 'sceneWork' && !is_null($value)) {
    $sceneWork = trim($value, " \t\n\r\0\x0B\"'");
    $scenesListRaw = $this->getProperty('scenesList');
    // Разбиваем список сцен на ассоциативный массив [код_сцены => имя_сцены]
    $sceneMap = [];
    $sceneItems = preg_split('/\s*(?:,|\r\n|\n|\r)\s*/', $scenesListRaw, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($sceneItems as $item) {
        [$name, $code] = array_map('trim', explode('=', $item, 2));
        if ($name && $code) {
            // Ключ - код сцены (sceneWork), Значение - имя сцены (sceneName)
            $sceneMap[$code] = $name;
        }
    }
    $sceneNameToSet = $sceneMap[$sceneWork] ?? 'unknown';
    // Устанавливаем найденное имя и сохраняем его
    if ($sceneNameToSet !== 'unknown') {
        $this->setProperty('sceneNameSaved', $sceneNameToSet);
    }
    // Обновляем sceneName для UI
    $this->setProperty('sceneName', $sceneNameToSet, 'worksUpdated');
}