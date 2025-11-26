<?php
/**
 * Обработчик изменения свойств RGB-ленты (color, level, sceneName).
 *
 * Метод выполняет:
 *  - обработку входящих значений цвета (HEX или пресеты: red, blue, lime и т.д.);
 *  - нормализацию цветовых и яркостных параметров;
 *  - обновление рабочих свойств устройства (colorWork, sceneWork);
 *  - переключение режима работы (colour / scene);
 *  - восстановление предыдущих значений, если пришло пустое значение;
 *  - обработку выбора сцены через свойство sceneName.
 *
 * Используемые свойства объекта:
 *  - level              — текущая яркость (1–100)
 *  - color              — текущий цвет (HEX)
 *  - colorSaved         — сохранённый цвет для восстановления
 *  - levelSaved         — сохранённая яркость
 *  - colorWork          — цвет в формате HSV-HEX для отправки устройству
 *  - sceneWork          — текущая активная сцена
 *  - sceneName          — имя выбранной пользователем сцены
 *  - sceneNameSaved     — сохранённая ранее сцена
 *  - scenesList         — список доступных сцен (формат "Имя=Значение,...")
 *  - status             — состояние устройства (0/1)
 *  - work_mode          — режим работы: "colour" или "scene"
 *
 * Параметры входящего события ($params):
 *  - NEW_VALUE   — новое значение свойства
 *  - PROPERTY    — имя изменяемого свойства
 *  - SOURCE      — источник изменения (для защиты от рекурсий)
 *
 * Логика обработки:
 *  1. Если пришли color или level — нормализуем, сохраняем и включаем устройство.
 *  2. Значение цвета может быть пресетом ('red','blue','lime'...) → преобразуется в HEX.
 *  3. Генерируем HSV-HEX строку (rgbToHSVhex) и записываем в colorWork.
 *  4. Если изменена сцена (sceneName) — ищем её в scenesList и активируем sceneWork.
 *  5. Если сцена не найдена — возвращаем сохранённую предыдущую.
 *  6. SOURCE=worksUpdated — используется для защиты от циклического обновления.
 *
 * @param array $params Ассоциативный массив с параметрами события.
 *
 * @return void
 */

// --- Дефолтные свойства
$this->callMethod('byDefault');

$source = strtok($params['SOURCE'], " ") ?? null;
if($source === 'worksUpdated') return;

$value = $params['NEW_VALUE'];
$transform = array(
	'red'      => '#ff0000',
	'green'    => '#00ff00',
	'blue'     => '#0000ff',
	'white'    => '#ffffff',
	'yellow'   => '#ffff00',
	'cyan'     => '#00ffff',
	'magenta'  => '#ff00ff',
	'orange'   => '#ffa500',
	'purple'   => '#800080',
	'pink'     => '#ffc0cb',
	'lime'     => '#00ff00'
	);
if (isset($transform[$value])) $value = $transform[$value];

$value = normalizeRange($value);
if ($value === null) return;

$colorSaved = $this->getProperty('colorSaved') ?? '#ffff00';
$level = normalizeRange($this->getProperty('level'),1);
$levelSaved = normalizeRange($this->getProperty('levelSaved'),1);
$property = $params['PROPERTY'] ?? null;
$status = $this->getProperty('status') ?? 0;
$sceneName = trim($this->getProperty('sceneName'), " \t\n\r\0\x0B\"'");
$sceneNameSaved = $this->getProperty('sceneNameSaved') ?? 'unknown';
$foundName = false;

if(in_array($property, ['color', 'level']) && $value != $this->getProperty($property)){
	$this->setProperty($property , $value, 'worksUpdated');
}

if(in_array($property, ['color', 'level'])){
	$this->setProperty('work_mode', 'colour');
	if($property === 'level')  $value = $colorSaved;
	if($property === 'color')  $level = $levelSaved;
	$hsvHex = rgbToHSVhex($value, $level)?: '003c03e801f4';
	$this->setProperty('colorWork', $hsvHex, 'propertysUpdated');
	if (!$status) $this->setProperty('status', 1);
}elseif($property=='sceneName' && $sceneName != 'unknown'){
	// Получаем список сцен и очищаем его от пробелов, кавычек и переводов строк по краям
	$scenesList = trim($this->getProperty('scenesList'), " \t\n\r\0\x0B\"'");
	// Разбиваем на отдельные сцены (по запятой или новой строке)
	$sceneItems = preg_split('/\s*(?:,|\r\n|\n|\r)\s*/', $scenesList, -1, PREG_SPLIT_NO_EMPTY);
	foreach ($sceneItems as $item) {
		// Каждая сцена имеет формат "Имя=Значение"
		$parts = explode('=', $item, 2); // ограничиваем на 2, чтобы значения с '=' не ломали парсинг
		if (count($parts) == 2) {
			$name  = $parts[0];
			$scene = $parts[1];
			// Если имя совпадает, обновляем sceneWork
			if ($name === $sceneName) {
				$foundName = true;
				$this->setProperty('work_mode', 'scene');
				$this->setProperty('sceneWork', $scene, 'propertysUpdated');
				if (!$status) $this->setProperty('status', 1);
				break; // нашли нужную сцену, дальше не ищем
			}
		}
	}
	if(!$foundName){
		$this->setProperty('sceneName', $sceneNameSaved);
	}
}

if ($source !== 'autoMode'){
	$this->setProperty('flag', 1);
	if(in_array($property, ['color', 'level'])){
		$this->setProperty($property.'Saved', $property === 'level'?$level:$value);
	}elseif($foundName){
		$this->setProperty('sceneNameSaved', $name);
	}
}
	
