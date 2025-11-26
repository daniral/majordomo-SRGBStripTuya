<?php
/** Краткое описание всех функций
 *
 * normalizeRange($val, $min, $max) — Проверяет и нормализует значение (число или HEX) в заданный диапазон.
 * dimmerTime($time, $addTime, $sign) — Вычисляет новое время с поправкой (добавить/вычесть HH:MM).
 * autoOff($object, $timer, $flag, $presence) — Запускает таймер автоотключения лампы.
 * initDefaults($object, $defaults) — Инициализирует свойства объекта по умолчанию.
 *
 * getAutoLevelCct($object, $level, $cct, $color, $colorLevel, $sceneName)
 * — Получает текущие значения яркости, CCT, цвета, уровня цвета и сцены для авто режима.
 *
 * adjustProperty($obj, $property, $value, $direction, $defaultStep, $min, $max)
 * — Универсальное изменение свойства (увеличить/уменьшить).
 *
 * createCommandsMenu($objectName, $menuItems, $parentId, $insertID, $depth)
 * — Создает меню управления объектом рекурсивно.
 *
 * deleteCommandsMenu($objectName, $menuItems)
 * — Удаляет команды меню по структуре $menuItems,
 * — используя TITLE и LINKED_OBJECT, включая вложенные
 * — команды по SUB_LIST (рекурсивно).
 * 
 * hsvToRgbHex($hsvHex) — Конвертирует 12-значный Tuya HSV в RGB HEX и яркость.
 * rgbToHSVhex($rgbHex, $brightness) — Конвертирует RGB HEX + яркость в Tuya HSV (12 hex цифр).
 *
 *--------------------------------------------------------------------------------------------
 *| Функция             | Назначение                                                         |
 *| ------------------- | -------------------------------------------------------------------|
 *| `normalizeRange`    | Нормализует число или HEX в диапазон                               |
 *| `dimmerTime`        | Корректирует время с учётом смещения                               |
 *| `autoOff`           | Таймер автоотключения лампы                                        |
 *| `initDefaults`      | Устанавливает свойства объекта по умолчанию                        |
 *| `getAutoLevelCct`   | Возвращает яркость, CCT, цвет, уровень цвета и сцену авто режима   |
 *| `adjustProperty`    | Универсальное изменение свойства лампы                             |
 *| `createCommandsMenu'| Рекурсивное создание меню управления объектом                      |
 *| `deleteCommandsMenu`| Удаление меню управления объектом                                  |
 *| `hsvToRgbHex`       | Преобразует Tuya HSV → RGB HEX и яркость                           |
 *| `rgbToHSVhex`       | Преобразует RGB HEX + яркость → Tuya HSV                           |
 *--------------------------------------------------------------------------------------------
 */
//


/** Проверяет и нормализует значение: числовое или HEX (цвет/яркость).
 * normalizeRange($val, $min, $max) 
 * @param mixed $val  Входное значение (число или HEX)
 * @param int $min    Минимальное значение диапазона
 * @param int $max    Максимальное значение диапазона
 * @return int|string|null Возвращает нормализованное число или HEX, либо null если невалидно
 */
 if (!function_exists('normalizeRange')) {
	function normalizeRange($val, $min = 0, $max = 100) {
		$val = strtolower(trim($val));
		if (preg_match('/^[0-9a-f]{12}$/i', $val)) {
			return $val;
		} elseif (preg_match('/^#?[0-9a-f]{6}$/i', $val)) {
			return $val;
		} elseif (is_numeric($val)) {
			return (int)max($min, min($max, $val));
		} else {
			return null;
		}
	}
}

/** Вычисляет новое время с поправкой.
 * dimmerTime($time, $addTime, $sign) 
 * @param string $time Исходное время HH:MM
 * @param string $addTime Коррекция HH:MM
 * @param int $sign 1=прибавить, 0=вычесть
 * @return string Скорректированное время HH:MM
 */
if (!function_exists('dimmerTime')) {
	function dimmerTime($time, $addTime, $sign=1) {
		$modifier = ($sign?'+':'-') . str_replace(':',' hours ',$addTime) . ' minutes';
		return date('H:i', strtotime("$time $modifier"));
	}
}

/** Запускает таймер автоотключения лампы.
 * autoOff($object, $timer, $flag, $presence)
 * @param object|string $object Объект лампы или его имя
 * @param string|int $timer Свойство таймера или конкретное число (сек)
 * @param string|int $flag Флаг блокировки авто режима
 * @param string|int $presence Свойство датчика присутствия
 */
if (!function_exists('autoOff')) {
	function autoOff($object, $timer='timerOff', $flag='flag', $presence='presence') {
		$object = is_object($object)?$object:(is_string($object)?getObject($object):null);
		if(!$object) return;

		$name = $object->object_title;
		$timerValue=120; $flagValue=0; $presenceValue=0;

		if(is_string($timer)) $timerValue=(int)($object->getProperty($timer)??120);
		elseif(is_numeric($timer)) $timerValue=(int)$timer;

		if(is_string($flag)) $flagValue=$object->getProperty($flag)??0;
		elseif(is_numeric($flag)) $flagValue=$flag;

		if(is_string($presence)) $presenceValue=$object->getProperty($presence)??0;
		elseif(is_numeric($presence)) $presenceValue=$presence;

		if($timerValue===0) return;
		$timerCode = "if(!getGlobal('{$name}.{$flag}') && !getGlobal('{$name}.{$presence}')) callMethod('{$name}.turnOff');";
		setTimeOut($name.'Timer', $timerCode, $timerValue);
	}
}

/** Инициализирует свойства объекта по умолчанию
 * initDefaults($object, $defaults)
 * @param object $object Объект лампы
 * @param array $defaults Массив ['свойство'=>'значение']
 */
if (!function_exists('initDefaults')) {
	function initDefaults($object, $defaults) {
		foreach($defaults as $prop=>$val) {
			if($object->getProperty($prop)=='') $object->setProperty($prop,$val);
		}
	}
}

/** Получает актуальные значения яркости, CCT, цвета и сцены для авто-режима лампы.
 * @param object      $object        Объект лампы (MajorDoMo object)
 * @param int|null    $level         Принудительное значение яркости (если указано)
 * @param int|null    $cct           Принудительное значение CCT (если указано)
 * @param string|null $color         Принудительное значение цвета (hex или raw Tuya)
 * @param int|null    $colorLevel    Принудительный уровень яркости цветного света
 * @param string|null $sceneName     Принудительное имя сцены
 * @param int|null    $dayNightMode  Принудительный режим (цвет, сцена)
 *
 * @return array{
 *     level:int|null,
 *     cct:int|null,
 *     color:string|null,
 *     colorLevel:int|null,
 *     sceneName:string|null
 * 	   dayNightMode:int|null
 * }
 */
if (!function_exists('getAutoLevelCct')) {
	function getAutoLevelCct($object, $level=null, $cct=null, $color=null, $colorLevel=null, $sceneName=null, $dayNightMode=null) {
		$dayBegin=$object->getProperty('dayBegin');
		$nightBegin=$object->getProperty('nightBegin');

		if($object->getProperty('workingBy')==2 &&
		   $object->getProperty('sunriseTime')!=$object->getProperty('sunsetTime')) {
			$dayBegin=dimmerTime($object->getProperty('sunriseTime'),$object->getProperty('addTimeSunrise'),$object->getProperty('signSunrise'));
			$nightBegin=dimmerTime($object->getProperty('sunsetTime'),$object->getProperty('addTimeSunset'),$object->getProperty('signSunset'));
		}

		$currentColor = null;
		$currentColorLevel = null;
		$currentLevel = null;
		$currentCct = null;
		$currentSceneName = null;
		$currentMode = null;

		if($object->getProperty('workingBy')!=3) {
			if(($object->getProperty('workingDay')==2 || $object->getProperty('workingDay')==3) && timeBetween($nightBegin,$dayBegin)) {
				$currentColor = $color ?? $object->getProperty('nightColor');
				$currentColorLevel = $colorLevel ?? $object->getProperty('nightColorLevel');
				$currentLevel = $level ?? $object->getProperty('nightLevel');
				$currentCct = $cct ?? $object->getProperty('nightCct');
				$currentSceneName = $sceneName ?? $object->getProperty('nightScene');
				$currentMode = $dayNightMode ?? $object->getProperty('nightMode') ?? '2';
			} elseif(($object->getProperty('workingDay')==1 || $object->getProperty('workingDay')==3) && timeBetween($dayBegin,$nightBegin)) {
				$currentColor = $color ?? $object->getProperty('dayColor');
				$currentColorLevel = $colorLevel ?? $object->getProperty('dayColorLevel');
				$currentLevel=$level ?? $object->getProperty('dayLevel');
				$currentCct=$cct ?? $object->getProperty('dayCct');
				$currentSceneName = $sceneName ?? $object->getProperty('dayScene');
				$currentMode = $dayNightMode ?? $object->getProperty('dayMode') ?? '2';
			}
		} elseif($object->getProperty('workingBy')==3 && $object->getProperty('illuminance')<=$object->getProperty('illuminanceMax')) {
			$currentColor = $color ?? $object->getProperty('nightColor');
			$currentColorLevel = $colorLevel ?? $object->getProperty('nightColorLevel');
			$currentLevel=$level ?? $object->getProperty('nightLevel');
			$currentCct=$cct ?? $object->getProperty('nightCct');
			$currentSceneName = $sceneName ?? $object->getProperty('nightScene');
			$currentMode = $dayNightMode ?? $object->getProperty('nightMode') ?? '2';
			$object->setProperty('illuminanceFlag',1);
		}
		return ['level'=>$currentLevel,'cct'=>$currentCct,'color'=>$currentColor,'colorLevel'=>$currentColorLevel,'sceneName'=>$currentSceneName,'dayNightMode'=>$currentMode];
	}
}

/** Универсальное изменение свойств (яркость, температура и т.п.)
 * adjustProperty($obj, $property, $value ?? null, $direction, $defaultStep, $min, $max);
 * @param object $obj — объект (обычно $this)
 * @param string $property — имя свойства ('level', 'cct' и т.п.)
 * @param mixed $value — шаг изменения (число или null)
 * @param string $direction — 'up' или 'down'
 * @param int $defaultStep — шаг по умолчанию (если не задан) = 10
 * @param int $min — минимальное значение (если не задан) = 0
 * @param int $max — максимальное значение (если не задан) = 100
 */
if (!function_exists('adjustProperty')) {
	function adjustProperty($obj, $property, $value = null, $direction = 'up', $defaultStep = 10, $min = 0, $max = 100)
    {
        $current = (int)$obj->getProperty($property);

        // Определяем шаг изменения
        $step = is_numeric($value) ? (int)$value : $defaultStep;
        $step = max(1, min($max, abs($step)));

        // Изменяем значение в нужную сторону
        $newValue = ($direction === 'up')
            ? min($max, $current + $step)
            : max($min, $current - $step);

        // Применяем новое значение
        $obj->callMethod("set" . ucfirst($property), ['value' => $newValue]);
    }
}

/** Создает меню для объекта рекурсивно.
 * createObjectMenu($objectName, $menuItems);
 * @param string $objectName Имя объекта, к которому привязываются команды.
 * @param array $menuItems Массив элементов меню. Каждый элемент должен быть массивом:
 * 	Формат пункта массива $menuItems= [[$objectName, '', '', '', '', '', '', '', '', '', '', '', [],'']];
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
 * 
 * @param int $parentId ID родительской команды. По умолчанию 0 (корень).
 * @param int $insertID Текущий последний ID команд. Используется для рекурсивной вставки.
 * @param int $depth Глубина рекурсии. Используется для определения, когда получать MAX(ID).
 *
 * @return int Возвращает последний использованный ID после вставки всех команд.
 */
if (!function_exists('createCommandsMenu')) {
	function createCommandsMenu($objectName, $menuItems, $parentId = 0, $insertID = 0, $depth = 0){
		// при первом вызове получаем максимальный ID из таблицы
		if ($depth === 0 && $insertID === 0) {
			$data = SQLSelectOne("SELECT MAX(ID) AS MAX_ID FROM commands");
			$insertID = $data['MAX_ID'] ?? 0;
		}

		foreach ($menuItems as $item) {

			$Record = [];
			$Record['ID'] = ++$insertID;
			$Record['PARENT_ID'] = $parentId;
			$Record['PARENT_LIST'] = ($parentId == 0) ? '0' : $parentId;
			
			// параметры
			$Record['TITLE'] = $item[0] ?? '';
			$Record['LINKED_OBJECT'] = $item[1] ?: $objectName;
			$Record['LINKED_PROPERTY'] = $item[2] ?? '';
			$Record['TYPE'] = $item[3] ?? '';
			$Record['CUR_VALUE'] = $item[4] ?? '';
			$Record['MIN_VALUE'] = (float)$item[5] ?? 0;
			$Record['MAX_VALUE'] = (float)$item[6] ?? 0;
			$Record['STEP_VALUE'] = (float)$item[7] ?? '';
			$Record['READ_ONLY'] = (int)$item[8] ?? 0;
			$Record['CODE'] = $item[9] ?? NULL;
			$Record['DATA'] = $item[10] ?? NULL;
			$Record['PRIORITY'] = $item[11] ?? 10;
			$Record['SUB_PRELOAD'] = isset($item[12]) ? 1 : 0;
			$Record['ICON'] = $item[13] ?? '';

			// добавляем команду
			SQLInsert('commands', $Record);

			// если есть подменю — рекурсия
			if (!empty($item[12]) && is_array($item[12])) {
				$firstChildId = $insertID + 1;
				$insertID = createCommandsMenu($objectName, $item[12], $Record['ID'], $insertID, $depth + 1);
				$lastChildId = $insertID;

				// обновляем SUB_LIST у родителя
				$childIds = range($firstChildId, $lastChildId);
				$subList = implode(',', $childIds);
				SQLExec("UPDATE commands SET SUB_LIST = '" . DBSafe($subList) . "' WHERE ID = '" . (int)$Record['ID'] . "'");
			}
		}

		return $insertID;
	}
}

/** Удаляет команды меню по структуре $menuItems,
 * используя TITLE и LINKED_OBJECT, включая вложенные
 * команды по SUB_LIST (рекурсивно).
 *
 * Работает на PHP 7 без предупреждений и ошибок.
 *
 * @param string $objectName
 * @param array  $menuItems
 */
if (!function_exists('deleteCommandsMenu')) {
	function deleteCommandsMenu($objectName, $menuItems)
	{
		$conditions = [];
		$stack = $menuItems;
		// 1. Сбор условий TITLE + LINKED_OBJECT
		while (!empty($stack)) {
			$item = array_pop($stack);
			$title = $item[0] ?? '';
			$linkedObject = $item[1] ?: $objectName;
			if ($title !== '') {
				$conditions[] = sprintf(
					"(TITLE='%s' AND LINKED_OBJECT='%s')",
					DBSafe($title),
					DBSafe($linkedObject)
				);
			}
			// добавляем подменю в стек
			if (!empty($item[12]) && is_array($item[12])) {
				foreach ($item[12] as $child) {
					$stack[] = $child;
				}
			}
		}
		// если нет условий — нечего удалять
		if (empty($conditions)) {
			return;
		}
		// 2. Получаем список ID команд + SUB_LIST
		$where = implode(" OR ", $conditions);
		$rows = SQLSelect("SELECT ID, SUB_LIST FROM commands WHERE {$where}");
		if (empty($rows)) {
			return;
		}
		// 3. Собираем все ID для удаления (сам пункт + его дети)
		$idsToDelete = [];
		foreach ($rows as $r) {
			$id = (int)$r['ID'];
			$idsToDelete[$id] = $id;
			if (!empty($r['SUB_LIST'])) {
				$children = explode(',', $r['SUB_LIST']);
				foreach ($children as $childId) {
					$childId = (int)$childId;
					if ($childId > 0) {
						$idsToDelete[$childId] = $childId;
					}
				}
			}
		}
		// 4. Удаляем все команды одним SQL
		$idList = implode(',', $idsToDelete);
		SQLExec("DELETE FROM commands WHERE ID IN ({$idList})");
	}
}

/** Конвертирует 12-значный Tuya HSV в RGB HEX и яркость.
 * hsvToRgbHex($hsvHex)
 * @param string $hsvHex 12-значный HEX
 * @return array ['rgbHex' => string, 'brightness' => int] RGB цвет и яркость
 */
if (!function_exists('hsvToRgbHex')) {
	function hsvToRgbHex($hsvHex) {
		$hsvHex = strtolower(trim($hsvHex));
		if (!preg_match('/^[0-9a-f]{12}$/', $hsvHex)) {
			return ['rgbHex' => '#ffff00', 'brightness' => 50];
		}
		$hueHex = substr($hsvHex, 0, 4);
		$satHex = substr($hsvHex, 4, 4);
		$valHex = substr($hsvHex, 8, 4);

		$hue = hexdec($hueHex);
		$sat = hexdec($satHex) / 1000;
		$val = hexdec($valHex) / 1000;

		$hue = max(0, min(360, $hue));
		$sat = max(0, min(1, $sat));
		$val = max(0, min(1, $val));

		$h = $hue / 60.0;
		$c = 1.0 * $sat;
		$x = $c * (1 - abs(fmod($h, 2) - 1));
		$m = 1.0 - $c;

		if ($h >= 0 && $h < 1)      { $r = $c; $g = $x; $b = 0; }
		elseif ($h < 2)             { $r = $x; $g = $c; $b = 0; }
		elseif ($h < 3)             { $r = 0; $g = $c; $b = $x; }
		elseif ($h < 4)             { $r = 0; $g = $x; $b = $c; }
		elseif ($h < 5)             { $r = $x; $g = 0; $b = $c; }
		else                        { $r = $c; $g = 0; $b = $x; }

		$r = round(($r + $m) * 255);
		$g = round(($g + $m) * 255);
		$b = round(($b + $m) * 255);

		$rgbHex = sprintf("#%02x%02x%02x", $r, $g, $b);
		$brightness = round($val * 100);

		return ['rgbHex' => $rgbHex, 'brightness' => $brightness];
	}
}

/** Конвертирует RGB HEX + яркость в Tuya HSV (12 hex цифр).
 * rgbToHSVhex($rgbHex, $brightness)
 * @param string $rgbHex Цвет в формате #RRGGBB
 * @param int $brightness Яркость 0-100
 * @return string|null 12-значный HSV HEX
 */
if (!function_exists('rgbToHSVhex')) {
	function rgbToHSVhex($rgbHex, $brightness = 100) {
		$rgbHex = ltrim($rgbHex, '#');
		$rgbHex = strtolower(trim($rgbHex));

		if (!preg_match('/^[0-9a-f]{6}$/', $rgbHex)) return null;

		$r = hexdec(substr($rgbHex,0,2))/255;
		$g = hexdec(substr($rgbHex,2,2))/255;
		$b = hexdec(substr($rgbHex,4,2))/255;

		$max = max($r,$g,$b);
		$min = min($r,$g,$b);
		$d = $max-$min;

		$h = 0;
		if ($d != 0) {
			if ($max==$r) { $h=fmod((($g-$b)/$d),6); }
			elseif ($max==$g) { $h=(($b-$r)/$d)+2; }
			else { $h=(($r-$g)/$d)+4; }
			$h *= 60;
			if ($h<0) $h+=360;
		}

		$s = $max==0?0:$d/$max;
		$val = $brightness*10;

		$hsv = str_pad(dechex(round($h)),4,'0',STR_PAD_LEFT)
			 . str_pad(dechex(round($s*1000)),4,'0',STR_PAD_LEFT)
			 . str_pad(dechex($val),4,'0',STR_PAD_LEFT);

		return strtolower($hsv);
	}
}
