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
 *
 * --- Режимы работы ---
 *  • work_mode="colour" — при изменении color или level.
 *  • work_mode="scene"  — при выборе сцены.
 *
 * --- Используемые свойства объекта ---
 *  • status           — включено/выключено (0/1)
 *  • level            — текущая яркость (1–100)
 *  • color            — текущий HEX-цвет
 *  • colorWork        — HSV-HEX строка для устройства
 *  • sceneName        — выбранная пользователем сцена
 *  • sceneNameSaved   — последняя корректная сцена
 *  • sceneWork        — активная сцена (код устройства)
 *  • scenesList       — список сцен: "Имя=Значение,Имя2=Значение2"
 *  • work_mode        — режим работы ("colour" или "scene")
 *
 * --- Параметры входящего события ---
 * @param array $params Ассоциативный массив:
 *      - string $params['PROPERTY']   Имя изменяемого свойства.
 *      - mixed  $params['NEW_VALUE']  Новое значение свойства.
 *      - string $params['SOURCE']     Источник события (защита от рекурсий).
 *
 * Логика обработки:
 *  1. Если SOURCE="worksUpdated" → выход (защита от рекурсий).
 *  2. Преобразование предустановок цвета (red, blue, lime ...).
 *  3. Нормализация числовых значений.
 *  4. Для color/level:
 *        - включение устройства,
 *        - установка work_mode=colour,
 *        - генерация colorWork,
 *        - сохранение *_Saved.
 *  5. Для sceneName:
 *        - поиск сцены,
 *        - установка sceneWork и work_mode=scene,
 *        - fallback на сохранённую сцену.
 *  6. Для scenesList:
 *        - очистка, валидация, пересборка,
 *        - синхронизация с commands.DATA.
 *  8. Cохранение colorSaved / levelSaved / sceneNameSaved.
 *
 * @return void
 */
//


// Инициализация свойств, если пусто.
if ($this->getProperty('scenesList') === '') {
    $this->setProperty('scenesList',
        'Яркий=05464601000003e803e800000000464601007803e803e80000000046460100f003e803e800000000464601003d03e803e80000000046460100ae03e803e800000000464601011303e803e800000000,
         Ослепительный=06464601000003e803e800000000464601007803e803e80000000046460100f003e803e800000000,
         Великолепный=07464602000003e803e800000000464602007803e803e80000000046460200f003e803e800000000464602003d03e803e80000000046460200ae03e803e800000000464602011303e803e800000000,
         Синее небо=1446460200ae03e803e80000000046460200b4012c03e80000000046460200b4003203e800000000,
         Океан=1746460200f003e803e80000000046460200dc02bc03e800000000,
         Подсолнух=184646020028032003e800000000464602001e038403e8000000004646020014038403e800000000,
         Лес=19464601007803e803e800000000464602006e0320025800000000464602005a038403e800000000,
         Кунг-Фу=1a464602000a038403e800000000464602000003e803e800000000,
         Фантазия=1c4646020104032003e800000000464602011802bc03e800000000464602011303e803e800000000,
         Средиземноморье=1d646401000003e803e80000000064640100f003e803e800000000646402007803e803e800000000646402003d03e803e800000000,
         Французский стиль=1e323201015e01f403e800000000323202003201f403e80000000032320200a001f403e800000000,
         Американский стиль=1f46460100dc02bc03e800000000464602006e03200258000000004646020014038403e800000000464601012703e802ee0000000046460100000384028a00000000,
         Хэллоуин=28464601011303e803e800000000464601001e03e803e800000000,
         Пасха=275a5a020014006403e800000000464602000003e803e800000000323202015e01f403e800000000464602011303e803e800000000,
         День победы=265a5a020014006403e800000000464602000003e803e800000000,
         Холи=25464601011303e803e800000000464602000003e803e800000000464602003d03e803e8000000004646010154032003e8000000004646010140032003e800000000464601001e02ee03e800000000,
         Дивали=24464602000003e803e800000000464602003d03e803e800000000464602011303e803e80000000046460200f003e803e800000000464602007803e803e800000000,
         День независимости=23505002000003e803e80000000046460200f003e803e800000000,
         Рождество=225a5a0100f003e803e8000000005a5a01003d03e803e800000000464601000003e803e8000000005a5a0100ae03e803e8000000005a5a01011303e803e800000000464601007803e803e800000000,
         День рождения=20646401003d03e803e800000000646401007803e803e8000000005a5a01011303e803e8000000005a5a0100ae03e803e800000000646401003201f403e800000000646401000003e803e800000000,
         Чтение=010e0d000084000003e800000000,
         Спокойной ночи=000e0d00002e03e802cc00000000,
         Весенние бутоны=09000000001e025803e800000000,
         Летняя прохлада=0a00000000b4006403e800000000,
         Осенняя прохлада=0b000000000f019003e800000000,
         Зимнее тепло=0c000000000a025803e800000000,
         Красный корал=0d000000000a02bc03e800000000,
         Оранжевая жизнь=0e000000001902ee03e800000000,
         Жёлтый сыр=0f000000001e025803e800000000,
         Степь=04464602007803e803e800000000464602007803e8000a00000000,
         Зелёная утка=1100000000b403e802bc00000000,
         Фиолетовый=13000000011303e803e800000000'
    );
}
if ($this->getProperty('level') == '') $this->setProperty('level', '50');
if ($this->getProperty('color') == '') $this->setProperty('color', '#ffff00');
if ($this->getProperty('sceneName') == '') $this->setProperty('sceneName', 'Синее небо');

$value    = $params['NEW_VALUE'] ?? null;

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
    : (($property === 'sceneName')
        ? ($params['NEW_VALUE'] ?? null) // Если sceneName (сырое значение)
        : normalizeRange($value, 1, 100, 'number')); // Иначе (level)

// --- Защита от рекурсий и не верных данных
if ($source === 'worksUpdated' || is_null($value)) {
    if(is_null($value)){
        $this->setProperty($property, $this->getProperty($property . 'Saved'), 'worksUpdated');
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
	// Если значение реально изменилось — сохраняем
    if ($value != $this->getProperty($property)) {
        $this->setProperty($property, $value, 'worksUpdated');
    }
    $this->setProperty($property . 'Saved', $value);
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

// // --- Цвет / Яркость
// if ($property === 'color' || $property === 'level') {
//     if ($property === 'color') {
// 		$norm = normalizeRange($value, 1, 100, 'color');
// 	} elseif ($property === 'level') {
// 		$norm = normalizeRange($value, 1, 100, 'number');
// 	}

//     if ($norm === null) {
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
//     $this->setProperty($property . 'Saved', $norm);
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
// 			$this->setProperty('sceneNameSaved', $name);
//             $this->setProperty('sceneWork', $scene, 'propertysUpdated');
//             if (!$this->getProperty('status')) $this->setProperty('status', 1);
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