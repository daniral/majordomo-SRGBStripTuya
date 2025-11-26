<?php
/**
 * Class SRGBStripTuya2
 *
 * Класс устройства RGB-ленты Tuya для MajorDoMo.
 * Наследуется от SControllers. Описывает свойства яркости, цвета,
 * рабочих параметров и сцен, а также методы управления устройством.
 *
 * ===========================================================
 * PROPERTIES:
 * ===========================================================
 *
 * @property string $color           Текущий цвет ленты (HEX 6 символов). 
 *                                   Формат: #RRGGBB или RRGGBB. DataKey. OnChange: propertysUpdated.
 *
 * @property string $colorWork       Рабочий цвет в формате HSV (12 HEX символов).
 *                                   Пример: "003c03e801f4". OnChange: worksUpdated.
 *
 * @property string $colorSaved      Последний установленный цвет (HEX).
 *
 * @property int    $level           Текущая яркость (1–100). 
 *                                   DataKey. OnChange: propertysUpdated.
 *
 * @property string $work_mode       Режим работы устройства. Возможные значения:
 *                                   "color" — управление цветом,
 *                                   "scene" — использование сцены.
 *
 * @property string $workScene       Текущая рабочая сцена. OnChange: worksUpdated.
 *
 * @property string $scenesList      Список доступных сцен в формате: "имя=значение,имя=значение,...".
 *
 * @property string $sceneName       Название текущей сцены. DataKey. OnChange: propertysUpdated.
 *
 * @property string $sceneNameSaved  Последнее активное название сцены.
 *
 * ===========================================================
 * METHODS:
 * ===========================================================
 *
 * @method void setLevel(int $value)
 *      Установить уровень яркости (0–100).  
 *      Вызывается через MajorDoMo: 
 *      `callMethod('Объект.setLevel', array("value" => 0–100))`
 *
 * @method void setColor(string $value)
 *      Установить цвет в HEX формате (#RRGGBB или RRGGBB).  
 *      Вызывается через MajorDoMo: 
 *      `callMethod('Объект.setColor', array("value" => "#RRGGBB"))`
 *
 * @method void levelUp(int $value = 10)
 *      Увеличить яркость на указанное значение.  
 *      Если параметр $value не передан, используется значение по умолчанию 10.  
 *      Вызывается через MajorDoMo: 
 *      `callMethod('Объект.levelUp', array("value" => 1–100))` или просто 
 *      `callMethod('Объект.levelUp')` для +10.
 *
 * @method void levelDown(int $value = 10)
 *      Уменьшить яркость на указанное значение.  
 *      Если параметр $value не передан, используется значение по умолчанию 10.  
 *      Вызывается через MajorDoMo: 
 *      `callMethod('Объект.levelDown', array("value" => 1–100))` или просто 
 *      `callMethod('Объект.levelDown')` для -10.
 *
 * @method void propertysUpdated()
 *      Вызывается при изменении яркости, цвета или сцены.
 *
 * @method void worksUpdated()
 *      Вызывается при изменении рабочих параметров (colorWork / workScene).
 */

if (SETTINGS_SITE_LANGUAGE && file_exists(ROOT . 'languages/SRGBStripTuya2_' . SETTINGS_SITE_LANGUAGE . '.php')) {
	include_once(ROOT . 'languages/SRGBStripTuya2_' . SETTINGS_SITE_LANGUAGE . '.php');
} else {
	include_once(ROOT . 'languages/SRGBStripTuya2_default.php'); //
}

$this->device_types['RGBStripTuya'] = array(
	'TITLE' => 'Освещение(RGB лента Tuya)',
	'PARENT_CLASS' => 'SControllers',
	'CLASS' => 'SRGBStripTuya2',
	'DESCRIPTION'=>'RGB лента(Tuya)',
	'PROPERTIES' => array(
		'color' => array('DESCRIPTION' => 'Цвет (RGB).', 'ONCHANGE' => 'propertysUpdated', 'DATA_KEY' => 1),
		'colorWork' => array('DESCRIPTION' => 'Рабочий цвет (HSV).', 'ONCHANGE' => 'worksUpdated'),
		'colorSaved' => array('DESCRIPTION' => 'Последний цвет.'),
		'level' => array('DESCRIPTION' => 'Яркость (0<-->100).', 'ONCHANGE' => 'propertysUpdated', 'DATA_KEY' => 1),
		'levelSaved' => array('DESCRIPTION' => 'Последняя яркость.', 'ONCHANGE' => 'propertysUpdated', 'DATA_KEY' => 1),
		'work_mode' => array('DESCRIPTION' => 'Режим работы.'),
		'workScene' => array('DESCRIPTION' => 'Рабочая сцена.', 'ONCHANGE' => 'worksUpdated'),
		'scenesList' => array('DESCRIPTION' => 'Список сцен.'),
		'sceneName' => array('DESCRIPTION' => 'Название текущей сцены.', 'ONCHANGE' => 'propertysUpdated', 'DATA_KEY' => 1),
		'sceneNameSaved' => array('DESCRIPTION' => 'Последняя сцена.'),
	),
	'METHODS' => array(
		'setLevel' => array('DESCRIPTION' => 'Установить уровень яркости.', '_CONFIG_SHOW' => 1, '_CONFIG_REQ_VALUE' => 1),
		'setColor' => array('DESCRIPTION' => 'Установиьт цвет(HEX).', '_CONFIG_SHOW' => 1, '_CONFIG_REQ_VALUE' => 1),
		'levelUp' => array('DESCRIPTION' => 'Увеличить яркость.', '_CONFIG_SHOW' => 1, '_CONFIG_REQ_VALUE' => 1),
		'levelDown' => array('DESCRIPTION' => 'Уменьшить яркость.', '_CONFIG_SHOW' => 1, '_CONFIG_REQ_VALUE' => 1),
		'worksUpdated' => array('DESCRIPTION' => 'Запускается при смене рабочих параметров'),
		'propertysUpdated' => array('DESCRIPTION' => 'Запускается при смене параметров'),
	),
);
