<?php

/**
 * Создает меню управления для объекта лампы.
 *    (Запускать 1 раз для каждого объекта)
 * 
 * Меню включает:
 *   - Основные элементы: Вкл/Выкл, Яркость, Температура
 *   - Автовключение с настройками таймера, рабочего режима и источника включения
 *   - Настройки по солнцу (восход, закат) с возможностью смещения времени
 *   - Настройки датчика освещенности
 *   - Настройки времени начала дня и ночи
 *   - Цветовые настройки: яркость и температура для дня и ночи, минимальные и максимальные значения
 *
 * Структура $menuItems соответствует стандарту MajorDoMo для createObjectMenu.
 *
 * @param string $objectName Название объекта (используется для идентификации в меню).
 * @param array $menuItems Массив элементов меню в формате MajorDoMo.
 *  Формат пункта массива $menuItems= [[$objectName, '', '', '', '', '', '', '', '', '', '', '', [],'']];
 * 
 * 	[
 * 		 0 => 'TITLE',          // Название команды
 * 		 1 => 'LINKED_OBJECT',  // Связанный объект (если пусто, используется $objectName)
 * 		 2 => 'LINKED_PROPERTY',// Свойство объекта
 * 		 3 => 'TYPE',           // Тип команды
 * 		 4 => 'CUR_VALUE',      // Текущее значение
 * 		 5 => 'MIN_VALUE',      // Минимальное значение
 * 		 6 => 'MAX_VALUE',      // Максимальное значение
 * 		 7 => 'STEP_VALUE',     // Шаг изменения
 *  	 8 => 'READ_ONLY',      // Только чтение (0 или 1)
 *  	 9 => 'CODE',           // Произвольный код
 *  	 10 => 'DATA',          // Дополнительные данные
 * 		 11 => 'PRIORITY',      // Приоритет команды
 *  	 12 => [ подменю ],     // Массив подменю (необязательный)
 *  	 13 => 'ICON',          // Иконка
 * 	]
 * @return void
 */
 
 
$objectName = $this->object_title;
$deleteMenu = $params['value'] ?? null;

// Получаем список сцен и очищаем его от лишних символов
$scenesList = trim($this->getProperty('scenesList'), " \t\n\r\0\x0B\"'");
// Разбиваем строки (запятая или перенос строки)
$sceneItems = preg_split('/\s*(?:,|\r\n|\n|\r)\s*/', $scenesList, -1, PREG_SPLIT_NO_EMPTY);
$sceneNamesExport = '';
foreach ($sceneItems as $item) {
    // Разбиваем "Имя = значение"
    $parts = preg_split('/\s*=\s*/', $item, 2);
    if (count($parts) === 2) {
        $name = $parts[0];
        $sceneNamesExport .= $name . "\r\n";
    }
}

$menuItems = [
    // Главное меню
    [$objectName, $objectName, '', '', '', '', '', '', '', '', '', 10, [
        ['Вкл/Выкл', $objectName, 'status', 'switch', '', '', '', '', '', "if (\$new_value) {callMethod('{$objectName}.turnOn');}else{callMethod('{$objectName}.turnOff');}", '', 120],
        ['Цвет', $objectName, 'color', 'color', '', '', '', '', '', '', '', 110],
        ['Яркость', $objectName, 'level', 'sliderbox', '', 1, 100, 1, '', '', '', 100],
		['Сцена', $objectName, 'sceneName', 'selectbox', '', '', '', '', '', '', $sceneNamesExport, 70],
        ['Режим', $objectName, 'mode', 'selectbox', '', '', '', '', '', '', "1=Цвет\r\n2=Сцена", 60],

        // Автовключение
        ['Автовключение', $objectName, '', '', '', '', '', '', '', '', '', 50, [
            ['Вкл/Выкл', $objectName, 'autoOnOff', 'switch', '', '', '', '', '', '', '', 40],
            ['Задержка(сек)', $objectName, 'timerOff', 'plusminus', '', 0, 10000, 5, '', '', '', 30],
            ['Включать', $objectName, 'workingDay', 'selectbox', '', '', '', '', '', '', "1=День\r\n2=Ночь\r\n3=24 часа", 20],
            ['Работать по', $objectName, 'workingBy', 'selectbox', '', '', '', '', '', '', "1=Время\r\n2=Солнце\r\n3=Датчик", 10],
        ]],

        // Время
        ['Время', $objectName, '', '', '', '', '', '', '', '', '', 40, [
            ['Начало Ночь', $objectName, 'nightBegin', 'timebox', '', -21600, 21600, 60, '', '', '', 20],
            ['Начало День', $objectName, 'dayBegin', 'timebox', '', -21600, 21600, 60, '', '', '', 10],
        ]],
		
        // Солнце
        ['Солнце', $objectName, '', '', '', '', '', '', '', '', '', 30, [
            ['Восход', $objectName, 'addTimeSunrise', 'timebox', '', -21600, 21600, 60, '', '', '', 40],
            ['Прибавить/Отнять', $objectName, 'signSunrise', 'selectbox', '', '', '', '', '', '', "1=Прибавить\r\n0=Отнять", 30],
            ['Закат', $objectName, 'addTimeSunset', 'timebox', '', -21600, 21600, 60, '', '', '', 20],
            ['Прибавить/Отнять', $objectName, 'signSunset', 'selectbox', '', '', '', '', '', '', "1=Прибавить\r\n0=Отнять", 10],
        ]],

        // Датчик
        ['Датчик', $objectName, '', '', '', '', '', '', '', '', '', 20, [
            ['Макс.Освещение', $objectName, 'illuminanceMax', 'plusminus', '', 0, 500, 1, '', '', '', 10],
        ]],

        // Цвет
        ['Цвет', $objectName, '', '', '', '', '', '', '', '', '', 10, [
            ['День', $objectName, '', '', '', '', '', '', '', '', '', 20, [
                ['Цвет', $objectName, 'dayColor', 'color', '', '', '', '', '', '', '', 60],
                ['Яркость', $objectName, 'dayLevel', 'sliderbox', '', 1, 100, 1, '', '', '', 50],
        		['Сцена', $objectName, 'dayScene', 'selectbox', '', '', '', '', '', '', $sceneNamesExport, 20],
        		['Режим', $objectName, 'dayMode', 'selectbox', '', '', '', '', '', '', "1=Цвет\r\n2=Сцена", 10],
            ]],
            ['Ночь', $objectName, '', '', '', '', '', '', '', '', '', 10, [
                ['Цвет', $objectName, 'nightColor', 'color', '', '', '', '', '', '', '', 60],
                ['Яркость', $objectName, 'nightLevel', 'sliderbox', '', 1, 100, 1, '', '', '', 50],
        		['Сцена', $objectName, 'nightScene', 'selectbox', '', '', '', '', '', '', $sceneNamesExport, 20],
            	['Режим', $objectName, 'nightMode', 'selectbox', '', '', '', '', '', '', "1=Цвет\r\n2=Сцена", 10],
            ]],
        ]],
    ],'SRGBStripTuya2.png']
];

if($deleteMenu === 'delete'){
    deleteCommandsMenu($objectName, $menuItems);
}else{
    createCommandsMenu($objectName, $menuItems);
}
