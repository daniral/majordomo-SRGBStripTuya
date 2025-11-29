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
 * @property string $sceneWork       Текущая рабочая сцена. OnChange: worksUpdated.
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
 *      Вызывается при изменении рабочих параметров (colorWork / sceneWork).
 */

if (SETTINGS_SITE_LANGUAGE && file_exists(ROOT . 'languages/SRGBStripTuya2_' . SETTINGS_SITE_LANGUAGE . '.php')) {
	include_once(ROOT . 'languages/SRGBStripTuya2_' . SETTINGS_SITE_LANGUAGE . '.php');
} else {
	include_once(ROOT . 'languages/SRGBStripTuya2_default.php'); //
}

$this->device_types['RGBStripTuya2'] = array(
	'TITLE' => 'Освещение(Tuya ZigBee LED strip) - 2',
	'PARENT_CLASS' => 'SControllers',
	'CLASS' => 'SRGBStripTuya2',
	'DESCRIPTION'=>'RGB лента(Tuya)2',
	'PROPERTIES' => array(
		'color' => array('DESCRIPTION' => 'Цвет (RGB).', 'ONCHANGE' => 'propertysUpdated', 'DATA_KEY' => 1),
		'colorWork' => array('DESCRIPTION' => 'Рабочий цвет (HSV).', 'ONCHANGE' => 'worksUpdated'),
		'colorSaved' => array('DESCRIPTION' => 'Последний цвет.'),

		'level' => array('DESCRIPTION' => 'Яркость (1<-->100).', 'ONCHANGE' => 'propertysUpdated', 'DATA_KEY' => 1),
		'levelSaved' => array('DESCRIPTION' => 'Последняя яркость.', 'DATA_KEY' => 1),

		'sceneName' => array('DESCRIPTION' => 'Название текущей сцены.', 'ONCHANGE' => 'propertysUpdated', 'DATA_KEY' => 1),
		'sceneWork' => array('DESCRIPTION' => 'Рабочая сцена.', 'ONCHANGE' => 'worksUpdated'),
		'sceneNameSaved' => array('DESCRIPTION' => 'Последняя сцена.'),
		'scenesList' => array('DESCRIPTION' => 'Список сцен.', 'ONCHANGE' => 'propertysUpdated'),

		'work_mode' => array('DESCRIPTION' => 'Режим работы.'),
		'mode' => array('DESCRIPTION' => 'Что включать (цвет, сцена)','_CONFIG_TYPE'=>'select','_CONFIG_OPTIONS'=>'1=Цвет,2=Сцена'),

		'dayColor' => array('DESCRIPTION' => 'Цвет днем', '_CONFIG_TYPE' => 'num',),
		'dayLevel' => array('DESCRIPTION' => 'Уровень яркости днем', '_CONFIG_TYPE' => 'num',),
		'dayScene' => array('DESCRIPTION' => 'Сцена днем', '_CONFIG_TYPE' => 'num',),
		'dayMode' => array('DESCRIPTION' => 'Что включать днем (цвет, сцена)','_CONFIG_TYPE'=>'select','_CONFIG_OPTIONS'=>'1=Цвет,2=Сцена'),

		'nightColor' => array('DESCRIPTION' => 'Цвет ночью', '_CONFIG_TYPE' => 'num',),
		'nightLevel' => array('DESCRIPTION' => 'Уровень яркости ночью', '_CONFIG_TYPE' => 'num',),
		'nightScene' => array('DESCRIPTION' => 'Сцена ночью', '_CONFIG_TYPE' => 'num',),
		'nightMode' => array('DESCRIPTION' => 'Что включать ночью (цвет, сцена)','_CONFIG_TYPE'=>'select','_CONFIG_OPTIONS'=>'1=Цвет,2=Сцена'),

		'autoOnOff' => array('DESCRIPTION' => 'Автовключение','_CONFIG_TYPE'=>'select','_CONFIG_OPTIONS'=>'1=Включено,0=Отключено'),
		'timerOff' => array('DESCRIPTION' => 'Выключить через(сек). 0-не выключать', '_CONFIG_TYPE' => 'num'),
		'workingDay' => array('DESCRIPTION' => 'Включать','_CONFIG_TYPE'=>'select','_CONFIG_OPTIONS'=>'1=Днём,2=Ночью,3=Круглосуточно'),
		'workingBy' => array('DESCRIPTION' => 'Работать по','_CONFIG_TYPE'=>'select','_CONFIG_OPTIONS'=>'1=Времени,2=Солнцу,3=Датчику'),
		'dayBegin' => array('DESCRIPTION' => 'Начало режима день(hh:mm)', '_CONFIG_TYPE' => 'num'),
		'nightBegin' => array('DESCRIPTION' => 'Начало режима ночь(hh:mm)', '_CONFIG_TYPE' => 'num'),
		'sunriseTime' => array('DESCRIPTION' => 'Время восхода солнца'),
		'sunsetTime' => array('DESCRIPTION' => 'Время захода солнца'),
		'signSunrise' => array('DESCRIPTION' => 'Восход','_CONFIG_TYPE'=>'select','_CONFIG_OPTIONS'=>'1=прибавить,0=отнять'),
		'addTimeSunrise' => array('DESCRIPTION' => 'Часов:Минут(00:00)', '_CONFIG_TYPE' => 'num'),
		'signSunset' => array('DESCRIPTION' => 'Закат','_CONFIG_TYPE'=>'select','_CONFIG_OPTIONS'=>'1=прибавить,0=отнять'),
		'addTimeSunset' => array('DESCRIPTION' => 'Часов:Минут(00:00)', '_CONFIG_TYPE' => 'num'),
		'illuminanceMax' => array('DESCRIPTION' => 'Макc.освещение(датчик)', '_CONFIG_TYPE' => 'num'),
		'illuminanceFlag' => array('DESCRIPTION' => 'Стопер датчика освещения'),
		'illuminance' => array('DESCRIPTION' => 'Данные с датчика освещения', 'DATA_KEY' => 1),
		'presence' => array('DESCRIPTION' => 'Данные с датчика присутствия', 'ONCHANGE' => 'propertysUpdated', 'DATA_KEY' => 1),
		'flag' => array('DESCRIPTION' => 'Стопер'),
	),
	'METHODS' => array(
		'turnOn' => array('DESCRIPTION' => 'Включить', '_CONFIG_SHOW' => 1),
		'turnOff' => array('DESCRIPTION' => 'Выключить', '_CONFIG_SHOW' => 1),
		'switch' => array('DESCRIPTION' => 'Переключить'),

		'levelUp' => array('DESCRIPTION' => 'Увеличить яркость.', '_CONFIG_SHOW' => 1, '_CONFIG_REQ_VALUE' => 1),
		'levelDown' => array('DESCRIPTION' => 'Уменьшить яркость.', '_CONFIG_SHOW' => 1, '_CONFIG_REQ_VALUE' => 1),
		'setLevel' => array('DESCRIPTION' => 'Установить уровень яркости.', '_CONFIG_SHOW' => 1, '_CONFIG_REQ_VALUE' => 1),

		'setColor' => array('DESCRIPTION' => 'Установиьт цвет(HEX).', '_CONFIG_SHOW' => 1, '_CONFIG_REQ_VALUE' => 1),
		
		'worksUpdated' => array('DESCRIPTION' => 'Запускается при смене рабочих параметров'),
		'propertysUpdated' => array('DESCRIPTION' => 'Запускается при смене параметров'),
		'statusUpdated' => array('DESCRIPTION' => 'Запускается при смене статуса'),

		'byDefault' => array('DESCRIPTION' => 'Установить свойства по умолчанию.'),
		'createCommandsMenu' => array('DESCRIPTION' => 'Создает меню управления.', '_CONFIG_SHOW' => 1),
		'deleteCommandsMenu' => array('DESCRIPTION' => 'Удаляет меню управления.', '_CONFIG_SHOW' => 1),	
	),
);
