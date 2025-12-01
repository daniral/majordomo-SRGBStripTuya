<?php
/** Обработчик изменения свойств RGB-ленты (presence, color, level, sceneName, scenesList).
 * 
 * Метод выполняет комплексную обработку входящих свойств устройства
 * и отвечает за:
 *
 * --- Цвет и яркость ---
 *  • Преобразование входящего цвета: HEX или предустановки (red, blue, lime и т.д.).
 *  • Нормализацию значений цвета и яркости.
 *  • Формирование строки HSV-HEX (rgbToHSVhex) и запись в colorWork.
 *  • Автоматическое включение устройства при изменении color или level.
 *  • Сохранение последних значений colorSaved и levelSaved.
 *
 * --- Сцены ---
 *  • Нормализацию и очистку свойства scenesList:
 *      - удаление переносов,
 *      - разбор формата "Имя=Значение",
 *      - выравнивание пробелов по краям,
 *      - отбрасывание невалидных элементов.
 *  • Обработку выбора сцены через sceneName:
 *      - поиск сцены в списке,
 *      - установка sceneWork,
 *      - переключение режима на "scene",
 *      - восстановление предыдущей сцены, если имя не найдено.
 *  • Синхронизацию списка сцен с таблицей commands:
 *      - обновление DATA для привязанных команд sceneName / dayScene / nightScene.
 *
 * --- Защита от рекурсий ---
 *  • SOURCE="worksUpdated" — предотвращает циклические обновления.
 *  • SOURCE="autoMode" — отключает запись в flag и сохранение значений.
 *
 * --- Режимы работы ---
 *  • work_mode="colour" — при изменении color или level.
 *  • work_mode="scene"  — при выборе сцены.
 *
 * --- Используемые свойства объекта ---
 *  • presence         — флаг присутствия (0/1)
 *  • status           — включено/выключено (0/1)
 *  • level            — текущая яркость (1–100)
 *  • color            — текущий HEX-цвет
 *  • levelSaved       — сохранённая яркость
 *  • colorSaved       — сохранённый цвет
 *  • colorWork        — HSV-HEX строка для устройства
 *  • sceneName        — выбранная пользователем сцена
 *  • sceneNameSaved   — последняя корректная сцена
 *  • sceneWork        — активная сцена (код устройства)
 *  • scenesList       — список сцен: "Имя=Значение,Имя2=Значение2"
 *  • work_mode        — режим работы ("colour" или "scene")
 *  • timerOff         — таймер авто-выключения
 *  • flag             — флаг изменения извне
 *
 * --- Параметры входящего события ---
 * @param array $params Ассоциативный массив:
 *      - string $params['PROPERTY']   Имя изменяемого свойства.
 *      - mixed  $params['NEW_VALUE']  Новое значение свойства.
 *      - string $params['SOURCE']     Источник события (защита от рекурсий).
 *
 * Логика обработки:
 *  1. byDefault() — установка дефолтов перед обработкой.
 *  2. Если SOURCE="worksUpdated" → выход (защита от рекурсий).
 *  3. Преобразование предустановок цвета (red, blue, lime ...).
 *  4. Нормализация числовых значений.
 *  5. Для color/level:
 *        - включение устройства,
 *        - установка work_mode=colour,
 *        - генерация colorWork,
 *        - сохранение *_Saved.
 *  6. Для sceneName:
 *        - поиск сцены,
 *        - установка sceneWork и work_mode=scene,
 *        - fallback на сохранённую сцену.
 *  7. Для scenesList:
 *        - очистка, валидация, пересборка,
 *        - синхронизация с commands.DATA.
 *  8. Если источник не autoMode:
 *        - запись flag=1,
 *        - сохранение colorSaved / levelSaved / sceneNameSaved.
 *
 * @return void
 */


// --- Дефолтные свойства
$this->callMethod('byDefault');

// --- Дефолтные свойства
$this->callMethod('byDefault');

$value    = $params['NEW_VALUE'] ?? null;

// --- Преобразование предустановок цвета
static $transform = [
    'red' => '#ff0000', 'green' => '#00ff00', 'blue' => '#0000ff',
    'white' => '#ffffff', 'yellow' => '#ffff00', 'cyan' => '#00ffff',
    'magenta' => '#ff00ff', 'orange' => '#ffa500', 'purple' => '#800080',
    'pink' => '#ffc0cb', 'lime' => '#00ff00'
];
if (isset($transform[$value])) {
    $value = $transform[$value];
}

$property = $params['PROPERTY'] ?? null;
$source   = strtok($params['SOURCE'] ?? '', ' ');
$value = ($property === 'color')
    ? normalizeRange($value, 1, 100, 'color') // Если color
    : (($property === 'sceneName' || $property === 'scenesList')
        ? ($params['NEW_VALUE'] ?? null) // Если sceneName (сырое значение)
        : (($property === 'presence')
            ? normalizeRange($value, 0, 1, 'number') // Если presence (0 или 1)
            : normalizeRange($value, 1, 100, 'number'))); // Иначе (level)

// --- Защита от рекурсий и не верных данных
if ($source === 'worksUpdated' || is_null($value)) {
    if(is_null($value)){
        $this->setProperty($property, $this->getProperty($property . 'Saved'), 'worksUpdated');
    }
    return;
}

// --- Обработка presence
if ($property === 'presence') {
    if ((int)$this->getProperty('timerOff') > 0) {
        autoOff($this);
    }
    return;
}

// --- Обработка Цвет / Яркость цвета
if ($property === 'color' || $property === 'level') {
    // Обновляем режим
    $this->setProperty('workMode', 'colour');
    // Генерация HSV-HEX
    $color = $property === 'color' ? $value : $this->getProperty('color');
    $level = $property === 'level' ? $value : $this->getProperty('level');
    $hsvHex = rgbToHSVhex($color, $level);
    $this->setProperty('colorWork', $hsvHex, 'propertysUpdated');
}

// --- Обработка выбора сцены
if ($property === 'sceneName') {
    $foundScen = false;
    $sceneName = trim($value, " \t\n\r\0\x0B\"'");
    if ($sceneName === '' || $sceneName === 'unknown') return;
    $scenesList = trim($this->getProperty('scenesList'), " \t\n\r\0\x0B\"'");
    if ($scenesList === '') return;
    $sceneItems = preg_split('/\s*(?:,|\r\n|\n|\r)\s*/', $scenesList, -1, PREG_SPLIT_NO_EMPTY);
    //  поиск
    foreach ($sceneItems as $item) {
        [$name, $scene] = array_pad(explode('=', $item, 2), 2, null);
        if ($name === $sceneName && $scene !== null) {
            $foundScen = true;
            $this->setProperty('workMode', 'scene');
            $this->setProperty('sceneWork', $scene, 'propertysUpdated');
            break;
        }
    }
    // Сцена не найдена → откат
    if(!$foundScen){
        $this->setProperty('sceneName', $this->getProperty('sceneNameSaved'));
        return;
    }
}

if ($property !== 'scenesList') {
    if (!$this->getProperty('status')) {
        $this->setProperty('status', 1);
    }
    if ($source !== 'autoMode') {
        $this->setProperty('flag', 1);
        $this->setProperty($property . 'Saved', $value);
    }
    // Если значение реально изменилось — сохраняем
    if ($value != $this->getProperty($property)) {
        $this->setProperty($property, $value, 'worksUpdated');
    }
    return;
}

// --- Обработка списка сцен scenesList
if ($property === 'scenesList') {
    $raw = $this->getProperty('scenesList');
    // Удаляем переносы
    $rawClean = str_replace(["\r", "\n"], '', $raw);
    // Разбиваем по запятым
    $items = explode(',', $rawClean);
    $valid = [];
    foreach ($items as $item) {
        $item = trim($item);
        if ($item === '' || strpos($item, '=') === false) continue;
        [$name, $val] = array_map('trim', explode('=', $item, 2));
        if ($name !== '' && $val !== '') {
            $valid[] = "$name=$val";
        }
    }
    $cleaned = implode(',', $valid);
    // Если изменилось — пишем и завершаемся (метод запустится заново)
    if ($cleaned !== $raw) {
        $this->setProperty('scenesList', $cleaned);
        return;
    }
    // --- Синхронизация команд
    $objectName = $this->object_title;
    $props = ['sceneName', 'dayScene', 'nightScene'];
    foreach ($props as $prop) {
        $rec = SQLSelectOne("
            SELECT * FROM commands
            WHERE LINKED_OBJECT='" . DBSafe($objectName) . "'
              AND LINKED_PROPERTY='" . DBSafe($prop) . "'
            LIMIT 1
        ");
        if (!$rec) continue;
        // Сцены из commands.DATA
        $cmdScenes = [];
        if ($rec['DATA'] !== '') {
            foreach (preg_split('/\R/', trim($rec['DATA'])) as $line) {
                $cmdScenes[] = trim(explode('=', $line)[0]);
            }
        }
        // Сцены из объекта
        $objScenes = [];
        foreach (explode(',', $cleaned) as $item) {
            $objScenes[] = trim(explode('=', $item)[0]);
        }
        // Сравнение и обновление
        if ($cmdScenes !== $objScenes) {
            $rec['DATA'] = implode("\r\n", $objScenes);
            SQLUpdate('commands', $rec);
        }
    }
    return;
}





// $property = $params['PROPERTY'] ?? null;
// $value    = $params['NEW_VALUE'] ?? null;
// $source   = strtok($params['SOURCE'] ?? '', ' ');

// // --- Защита от рекурсий
// if ($source === 'worksUpdated') return;

// // --- Преобразование предустановок цвета
// static $transform = [
//     'red' => '#ff0000', 'green' => '#00ff00', 'blue' => '#0000ff',
//     'white' => '#ffffff', 'yellow' => '#ffff00', 'cyan' => '#00ffff',
//     'magenta' => '#ff00ff', 'orange' => '#ffa500', 'purple' => '#800080',
//     'pink' => '#ffc0cb', 'lime' => '#00ff00'
// ];
// if (isset($transform[$value])) {
//     $value = $transform[$value];
// }

// // --- Обработка presence
// if ($property === 'presence') {
//     if ((int)$this->getProperty('timerOff') > 0) {
//         autoOff($this);
//     }
//     return;
// }
// // --- Цвет / Яркость
// if ($property === 'color' || $property === 'level') {
//     if ($property === 'color') {
// 		$norm = normalizeRange($value, 1, 100, 'color');
// 	} elseif ($property === 'level') {
// 		$norm = normalizeRange($value, 1, 100, 'number');
// 	}
//     if (is_null($norm)) {
// 		$this->setProperty($property, $this->getProperty($property . 'Saved'), 'worksUpdated');
// 		return;
// 	}
//     // Обновляем режим
//     $this->setProperty('work_mode', 'colour');
//     // Если значение реально изменилось — сохраняем
//     if ($norm != $this->getProperty($property)) {
//         $this->setProperty($property, $norm, 'worksUpdated');
//     }
//     // Генерация HSV-HEX
//     $color = $property === 'color' ? $norm : $this->getProperty('color');
//     $level = $property === 'level' ? $norm : $this->getProperty('level');
//     $hsvHex = rgbToHSVhex($color, $level);
//     $this->setProperty('colorWork', $hsvHex, 'propertysUpdated');
//     // Автовключение
//     if (!$this->getProperty('status')) {
//         $this->setProperty('status', 1);
//     }
//     // Сохранение
//     if ($source !== 'autoMode') {
//         $this->setProperty('flag', 1);
//         $this->setProperty($property . 'Saved', $norm);
//     }
//     return;
// }

// // --- Обработка выбора сцены
// if ($property === 'sceneName') {
//     $sceneName = trim($value, " \t\n\r\0\x0B\"'");
//     if ($sceneName === '' || $sceneName === 'unknown') return;
//     $scenesList = trim($this->getProperty('scenesList'), " \t\n\r\0\x0B\"'");
//     if ($scenesList === '') return;
//     $sceneItems = preg_split('/\s*(?:,|\r\n|\n|\r)\s*/', $scenesList, -1, PREG_SPLIT_NO_EMPTY);
//     // Оптимизированный поиск
//     foreach ($sceneItems as $item) {
//         [$name, $scene] = array_pad(explode('=', $item, 2), 2, null);
//         if ($name === $sceneName && $scene !== null) {
//             $this->setProperty('work_mode', 'scene');
//             $this->setProperty('sceneWork', $scene, 'propertysUpdated');
//             if (!$this->getProperty('status')) {
//                 $this->setProperty('status', 1);
//             }
//             if ($source !== 'autoMode') {
//                 $this->setProperty('flag', 1);
//                 $this->setProperty('sceneNameSaved', $name);
//             }
//             return;
//         }
//     }
//     // Сцена не найдена → откат
//     $this->setProperty('sceneName', $this->getProperty('sceneNameSaved'));
//     return;
// }

// // --- Обработка списка сцен scenesList
// if ($property === 'scenesList') {
//     $raw = $this->getProperty('scenesList');
//     // Удаляем переносы
//     $rawClean = str_replace(["\r", "\n"], '', $raw);
//     // Разбиваем по запятым
//     $items = explode(',', $rawClean);
//     $valid = [];
//     foreach ($items as $item) {
//         $item = trim($item);
//         if ($item === '' || strpos($item, '=') === false) continue;
//         [$name, $val] = array_map('trim', explode('=', $item, 2));
//         if ($name !== '' && $val !== '') {
//             $valid[] = "$name=$val";
//         }
//     }
//     $cleaned = implode(',', $valid);
//     // Если изменилось — пишем и завершаемся (метод запустится заново)
//     if ($cleaned !== $raw) {
//         $this->setProperty('scenesList', $cleaned);
//         return;
//     }
//     // --- Синхронизация команд
//     $objectName = $this->object_title;
//     $props = ['sceneName', 'dayScene', 'nightScene'];
//     foreach ($props as $prop) {
//         $rec = SQLSelectOne("
//             SELECT * FROM commands
//             WHERE LINKED_OBJECT='" . DBSafe($objectName) . "'
//               AND LINKED_PROPERTY='" . DBSafe($prop) . "'
//             LIMIT 1
//         ");
//         if (!$rec) continue;
//         // Сцены из commands.DATA
//         $cmdScenes = [];
//         if ($rec['DATA'] !== '') {
//             foreach (preg_split('/\R/', trim($rec['DATA'])) as $line) {
//                 $cmdScenes[] = trim(explode('=', $line)[0]);
//             }
//         }
//         // Сцены из объекта
//         $objScenes = [];
//         foreach (explode(',', $cleaned) as $item) {
//             $objScenes[] = trim(explode('=', $item)[0]);
//         }
//         // Сравнение и обновление
//         if ($cmdScenes !== $objScenes) {
//             $rec['DATA'] = implode("\r\n", $objScenes);
//             SQLUpdate('commands', $rec);
//         }
//     }
//     return;
// }