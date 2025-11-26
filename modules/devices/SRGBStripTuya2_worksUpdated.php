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
 * - normalizeRange($val, $min, $max) — нормализует числовое значение.
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

$source = strtok($params['SOURCE'], " ");
if($source === 'propertysUpdated') return;

$value = normalizeRange($params['NEW_VALUE'], 1, 1000);
$level = $this->getProperty('level');
$property = $params['PROPERTY'];
$sceneNameToSet = 'unknown';


if(in_array($property, ['colorWork']) && !is_null($value)){
	$data = hsvToRgbHex($value);
	$level = $data['brightness'];
	$colorRGB = $data['rgbHex'];
	$this->setProperty('color', $colorRGB, 'worksUpdated');
	$this->setProperty('colorSaved', $colorRGB);
	$this->setProperty('level', $level, 'worksUpdated');
}elseif(in_array($property, ['sceneWork']) && !is_null($params['NEW_VALUE'])){
	$sceneWork = trim($params['NEW_VALUE'], " \t\n\r\0\x0B\"'");
	
	// Получаем список сцен и очищаем его от пробелов и кавычек по краям
	$scenesList = trim($this->getProperty('scenesList'), " \t\n\r\0\x0B\"'");

	// Разбиваем на отдельные сцены (по запятой или переносу строки)
	$sceneItems = preg_split('/\s*(?:,|\r\n|\n|\r)\s*/', $scenesList, -1, PREG_SPLIT_NO_EMPTY);

	// Перебираем массив сцен
	foreach ($sceneItems as $item) {
		// Каждая сцена имеет формат "Имя=Значение"
		$parts = explode('=', $item, 2); 
		if (count($parts) == 2) {
			$name  = $parts[0];
			$scene = $parts[1];
			// Если значение совпадает, обновляем sceneName
			if ($sceneWork === $scene) {
				$sceneNameToSet = $name;
				$this->setProperty('sceneNameSaved', $sceneNameToSet);
				break; // нашли нужную сцену, дальше не ищем
			}
		}
	}
	$this->setProperty('sceneName', $sceneNameToSet, 'worksUpdated');
}