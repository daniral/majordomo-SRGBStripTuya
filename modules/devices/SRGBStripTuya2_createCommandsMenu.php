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
        ['Вкл/Выкл', $objectName, 'status', 'switch', '1', '', '', '', '', '', '', 120],
        ['Цвет', $objectName, 'color', 'color', '', '', '', '', '', "callMethod('{$objectName}.setColor', array('value' => \$new_value));", '', 110],
        ['Яркость', $objectName, 'level', 'sliderbox', '50', 1, 100, 1, '', "callMethod('{$objectName}.setColorLevel', array('value' => \$new_value));", '', 100],
		['Сцена', $objectName, 'sceneName', 'selectbox', '2', '', '', '', '', '', $sceneNamesExport, 70],
        ['Режим', $objectName, 'mode', 'selectbox', '2', '', '', '', '', '', "1=Цвет\r\n2=Сцена", 60],

        // Автовключение
        ['Автовключение', $objectName, '', '', '', '', '', '', '', '', '', 50, [
            ['Вкл/Выкл', $objectName, 'autoOnOff', 'switch', '1', '', '', '', '', '', '', 40],
            ['Задержка(сек)', $objectName, 'timerOff', 'plusminus', '40', 0, 10000, 5, '', '', '', 30],
            ['Включать', $objectName, 'workingDay', 'selectbox', '2', '', '', '', '', '', "1=Днем\r\n2=Ночью\r\n3=Круглосутлчно", 20],
            ['Работать по', $objectName, 'workingBy', 'selectbox', '2', '', '', '', '', '', "1=Времени\r\n2=Солнцу\r\n3=Датчику", 10],
        ]],

        // Солнце
        ['Солнце', $objectName, '', '', '', '', '', '', '', '', '', 40, [
            ['Восход', $objectName, 'addTimeSunrise', 'timebox', '00:00', -21600, 21600, 60, '', '', '', 40],
            ['Прибавить/Отнять', $objectName, 'signSunrise', 'selectbox', '1', '', '', '', '', '', "1=Прибавить\r\n0=Отнять", 30],
            ['Закат', $objectName, 'addTimeSunset', 'timebox', '00:30', -21600, 21600, 60, '', '', '', 20],
            ['Прибавить/Отнять', $objectName, 'signSunset', 'selectbox', '1', '', '', '', '', '', "1=Прибавить\r\n0=Отнять", 10],
        ]],

        // Датчик
        ['Датчик', $objectName, '', '', '', '', '', '', '', '', '', 30, [
            ['Макс.Освещение', $objectName, 'illuminanceMax', 'plusminus', '15', 0, 500, 1, '', '', '', 10],
        ]],

        // Время
        ['Время', $objectName, '', '', '', '', '', '', '', '', '', 20, [
            ['Начало Ночь', $objectName, 'nightBegin', 'timebox', '18:00', -21600, 21600, 60, '', '', '', 20],
            ['Начало День', $objectName, 'dayBegin', 'timebox', '08:00', -21600, 21600, 60, '', '', '', 10],
        ]],

        // Цвет
        ['Цвет', $objectName, '', '', '', '', '', '', '', '', '', 10, [
            ['Днем', $objectName, '', '', '', '', '', '', '', '', '', 20, [
                ['Цвет', $objectName, 'dayColor', 'color', '#FFFFFF', '', '', '', '', '', '', 60],
                ['Яркость', $objectName, 'dayLevel', 'sliderbox', '100', 1, 100, 1, '', '', '', 50],
        		['Сцена', $objectName, 'dayScene', 'selectbox', '', '', '', '', '', '', $sceneNamesExport, 20],
        		['Режим', $objectName, 'dayMode', 'selectbox', '2', '', '', '', '', '', "1=Цвет\r\n2=Сцена", 10],
            ]],
            ['Ночью', $objectName, '', '', '', '', '', '', '', '', '', 10, [
                ['Цвет', $objectName, 'nightColor', 'color', '#FFFF00', '', '', '', '', '', '', 60],
                ['Яркость', $objectName, 'nightLevel', 'sliderbox', '30', 1, 100, 1, '', '', '', 50],
        		['Сцена', $objectName, 'nightScene', 'selectbox', '2', '', '', '', '', '', $sceneNamesExport, 20],
            	['Режим', $objectName, 'nightMode', 'selectbox', '2', '', '', '', '', '', "1=Цвет\r\n2=Сцена", 10],
        ]],
        ]],
    ],'RGBStripTuya2.png']
];

if($deleteMenu === 'delete'){
    deleteCommandsMenu($objectName, $menuItems);
}else{
    createCommandsMenu($objectName, $menuItems);
}
