<?php

if ($this->getProperty('colorBrightness') == '') $this->setProperty('colorBrightness', '50');
if ($this->getProperty('color') == '') $this->setProperty('color', '#ffff00');

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
$colorSaved = $this->getProperty('colorSaved') ?? '#ffff00';
$colorBrightness = normalizeRange($this->getProperty('colorBrightness'),1);
$source = strtok($params['SOURCE'], " ") ?? null;
$property = $params['PROPERTY'] ?? null;
$status = $this->getProperty('status') ?? 0;
$sceneName = trim($this->getProperty('sceneName'), " \t\n\r\0\x0B\"'");
$sceneNameSaved = $this->getProperty('sceneNameSaved') ?? 'unknown';

if(!is_null($value)) $this->setProperty($property , $value, 'worksUpdated');

if(in_array($property, ['color', 'colorBrightness']) && !is_null($value) && $source != 'worksUpdated'){
	if($property == 'colorBrightness')  $value = $colorSaved;
	$this->setProperty('work_mode', 'colour');
	$hsvHex = rgbToHSVhex($value, $colorBrightness)?: '003c03e801f4';
	$this->setProperty('colorWork', $hsvHex, 'propertysUpdated');
	if (!$status) $this->setProperty('status', 1);
	$this->setProperty('colorSaved', $value);
}elseif(in_array($property, ['sceneName']) && $sceneName != 'unknown' && $source != 'worksUpdated'){
	// Получаем список сцен и очищаем его от пробелов, кавычек и переводов строк по краям
	$scenesList = trim($this->getProperty('scenesList'), " \t\n\r\0\x0B\"'");
	// Разбиваем на отдельные сцены (по запятой или новой строке)
	$sceneItems = preg_split('/\s*(?:,|\r\n|\n|\r)\s*/', $scenesList, -1, PREG_SPLIT_NO_EMPTY);
	$foundName = false;
	foreach ($sceneItems as $item) {
		// Каждая сцена имеет формат "Имя=Значение"
		$parts = explode('=', $item, 2); // ограничиваем на 2, чтобы значения с '=' не ломали парсинг
		if (count($parts) == 2) {
			$name  = $parts[0];
			$scene = $parts[1];
			// Если имя совпадает, обновляем workScene
			if ($name === $sceneName) {
				$foundName = true;
				$this->setProperty('work_mode', 'scene');
				$this->setProperty('workScene', $scene, 'propertysUpdated');
				$this->setProperty('sceneNameSaved', $name);
				if (!$status) $this->setProperty('status', 1);
				break; // нашли нужную сцену, дальше не ищем
			}
		}
	}
	if(!$foundName){
		$this->setProperty('sceneName', $sceneNameSaved);
	}
}