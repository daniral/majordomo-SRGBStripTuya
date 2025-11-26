<?php
/*
# ** 💡 Лампочка Guver Lanp (Tuya).**  
## **Простое устройство для MajorDomo.**   
Добавление в MajorDomo простого устройства для лампочеи Лампочка Guver Lanp (Tuya).  
Управление цветом, яркостью, теплотой и сценами.   
Расширяет встроенный класс SControllers.  
Добавляет новый класс **`RGBStripTuya2`**.
С авто режимом включеня по датчику освещения, восходу/закату солнца или по установленному времени.  
С заданными цветом, яркостью, теплотой, сценой для дня и ночи.  
Автовыключение через заданное времени.  
Авто режим для Дня, Ночи или в течении всего деня.  

## ⚙️ Привязка свойств  

- **switch_led   --> status**  
- **work_mode    --> modeWork**  
- **bright_value --> levelWork**  
- **temp_value   --> cctWork**  
- **colour_data  --> colorWork**  
- **scene_data   --> sceneWork**  

### **ОБЫЧНЫЙ РЕЖИМ:**  

Включить - callMethod('имя объекта '.'turnOn');  

Без параметров : 

  - levelSaved       если не заполнено - 100  
  - cctSaved         если не заполнено - 100   
  - colorSaved       если не заполнено - #FFFFFF  
  - colorLevelSaved  если не заполнено - 100  
  - sceneNameSaved   если не заполнено - Спокойная  

С параметрами:  
- callMethod('имя объекта.turnOn', array('level'=> 1<-->100,   
                                          'cct'=> 1<-->100,  
                                          'color'=> 1<-->100,  
                                          'colorLevel'=> #RRGGBB,  
                                          'sceneName'=> имя из списка сцен));  
 
**Устанавливается flag=1. Стопер который не дает запускаться авто режиму и методу autoOff.**  

### **АВТО РЕЖИМ:**  

Включить авто режим - callMethod('имя объекта.turnOn', array('autoMode'=>1)) 
к пимеру запускать по датчику движения;   
- Включится на время которое указано в timerOff(сек). Если 0 то включится но сам не выключится.  
- Если в presence(например данные с датчика присутствия) 1 то не выключится.  
  - Как только в presence изменися с 1 на 0 запустится автовыключение(autoOff).    
- Авто режим для Дня, Ночи или Круглосуточно.   
  - в workingDay:   
    + 1 - День  (дневные установки)  
    + 2 - Ночь  (ночные установки)  
    + 3 - Круглосуточно.(Ночью ночные установки яркости и теплоты. Днем дневные.)  
	
- Авто режим по времени (workingBy=1):  
    - после начало ночь - ночные установки яркости и теплоты.  
    - после начло день - дневные.  
- Авто режим по солнцу (workingBy=2):  
  - после захода - ночные установки .  
  - после восхода - дневные установки.  
  - Надо обязательно писать в свойства sunriseTime и sunsetTime время восхода и заката.  
    Если не указано, то то, что указано - по времени.  
  - К восходу и закату можно прибавить или отнять время если надо чтобы включалось или выключалось раньше или позже:  
    - addTimeSunrise - к рассвету в формате 05:30 (5 часов 30 минут)  
      - signSunrise 0 - отнять 1 - прибавить.  
    - addTimeSunset  - к закату в формате 00:30 (30 минут)  
      - signSunset 0 - отнять 1 - прибавить.  
- Авто режим по датчику (workingBy=3):  
    - Только ночные установки яркости и теплоты.  
    - В свойство illuminance надо писать данные с датчика освещения.  
      - если illuminance меньше чем установленно в illuminanceMax подсветка включится.  
    - ***Работу по датчику освещения не проверял так как не имеется в наличии.***   
- **Можно запустить авто режим с параметрами:**  
  - callMethod('имя объекта.turnOn', array('autoMode'=>1,  
                                           'level'=> 1<-->100,   
                                           'cct'=> 1<-->100,  
                                           'color'=> 1<-->100,  
                                           'colorLevel'=> #RRGGBB,  
                                           'sceneName'=> имя из списка сцен));  

                                           ## 🎨 Работа со сценами


## **🎨 СЦЕНЫ:**  

### 🧾 `scenesList`

Хранит список доступных сцен в формате:

Название=Значение,Название=Значение,...

**Пример:**
Спокойная=000e0d0000000000000000c80000,Чтение=010e0d0000000000000003e801f4,Работа=020e0d0000000000000003e803e8

- Если свойство `scenesList` пустое — оно автоматически заполнится дефолтными сценами.  
- Можно редактировать список вручную, добавлять свои сцены или полностью заменить его.  

---

### 🏷 `sceneName`

Хранит **имя текущей активной сцены**.

- Можно установить сцену по имени (Majordomo автоматически подставит нужный код).  
- Если указанное имя отсутствует в списке — установится **последняя сохранённая сцена**.  
- Если в `sceneName` записано `"unknown"` — в интерфейсе отображается **«Неизвестная сцена»**, но список сцен остаётся доступен для выбора.

## **🔧 МЕТОДЫ:**  

- **turnOff**  
  - Выключить - callMethod('имя объекта '.'turnOff');  
    - Устанавливается flag = 0
    - - **turnOff**  
- **switch**  
 - Переключить - callMethod('имя объекта '.'switch');  
    - Если лампа включена в авто-режиме  
      — включает сохранённые значения.  
    - Если лампа выключена  
      — включает сохранённые значения.  
    - Если лампа включена не в авто-режиме — выключает её.  

- **setColor**   
  - Установить цвет.(array("value"=> '#RRGGBB'))  
    - **flag=1** - авто режим и автовыключение не запустится.  
- **setColorLevel**   
  - Установить яркость цвета.(array("value"=> 1 <--> 100 %))  
    - **flag=1** - авто режим и автовыключение не запустится.  
- **colorLevelDown**  
  - Уменьшить яркость цвета.(array("value"=>1--100)). Без  параметров -10.  
  - **flag=1** - авто режим и автовыключение не запустится.  
- **colorLevelUp**  
  - Увеличить яркость цвета.(array("value"=>1--100)). Без  параметров 10.  
  - **flag=1** - авто режим и автовыключение не запустится.  

- **setLevel**   
  - Установить яркость белого света.(array("value"=> 1 <--> 100 %))  
    - **flag=1** - авто режим и автовыключение не запустится.  
- **levelDown**  
  - Уменьшить яркость белого света.(array("value"=>1--100)). Без  параметров -10.  
  - **flag=1** - авто режим и автовыключение не запустится.  
- **levelUp**  
  - Увеличить яркость белого света.(array("value"=>1--100)). Без  параметров 10.  
  - **flag=1** - авто режим и автовыключение не запустится.  

- **setCct**   
  - Установить температуру белого света.(array("value"=>1 <--> 100 %))  
    - Вместо процентов можно вызвать пресеты:'coolest','cool','warm','warmest'.  
    - **flag=1** - авто режим и автовыключение не запустится.  
- **cctDown**  
  - Уменьшить температуру белого света.(array("value"=>1--100)). Без  параметров -10.  
  - **flag=1** - авто режим и автовыключение не запустится.  
- **cctUp**  
  - Увеличить температуру белого света.(array("value"=>1--100)). Без  параметров 10.  
  - **flag=1** - авто режим и автовыключение не запустится.  

- **byDefault**  
  - Установит параметры по дефолту. Это если что-то пошло не так.  
    (При первом запуске метода turnOn тоже все выставится по дефолту.)  
- **createCommandsMenu**  
  - Создаст меню данного объекта в **Меню Управления**   
        Где можно все удобно настроить.  
        Меню будет называться по имени объекта. При желании можно изменить на любое другое. 
- **deleteCommandsMenu**  
  - Удалит меню данного объекта в "Меню Управления"  

При первом запуске метода **turnOn** все нужные свойства для работы устройства должны прописаться сами.  */

/**
 *
 * @param array $params Массив входных параметров для управления лампой:
 *   - int|null   $params['level']       Уровень яркости (0–100). Если 0 — лампа выключается.
 *   - string|null $params['color']      Цвет в HEX формате (например, '#FFFFFF').
 *   - int|null   $params['colorLevel']  Яркость цвета (0–100).
 *   - int|null   $params['cct']         Теплота света (CCT) (0–100).
 *   - string|null $params['sceneName']  Название сцены (например, 'Спокойная').
 *   - bool|int   $params['autoMode']    Включение авто режима (1 — включен, 0 — выключен).
 *
 * @property string $color         Текущий цвет лампы (HEX), сохраняется при режиме color.
 * @property int    $colorLevel    Яркость цвета (0–100), сохраняется при режиме color.
 * @property int    $level         Уровень яркости белого света (0–100).
 * @property int    $cct           Теплота света (CCT) (0–100).
 * @property string $sceneName     Название текущей сцены.
 * @property bool   $autoMode      Флаг авто режима.
 * @property int    $mode          Текущий режим работы лампы:
 *                                1 — цветной свет,
 *                                2 — белый свет,
 *                                3 — сцена.
 * @property bool   $flag          Внутренний флаг для авто режима (защита от повторного вызова).
 * @property int    $timerOff      Время авто-выключения (в секундах), 0 — отключено.
 *
 * @return void
 *
 * @note
 */
//

// --- Дефолтные свойства
$this->callMethod('byDefault');

// --- Если level=0, выключаем
if (($params['level'] ?? 1) == 0) {
  $this->callMethod('turnOff');
  return;
}

$color = $params['color'] ?? null;
$colorSaved = $this->getProperty('colorSaved');
$colorLevel = $params['colorLevel'] ?? null;
$colorLevelSaved = $this->getProperty('colorLevelSaved');

$level = $params['level'] ?? null;
$levelSaved = $this->getProperty('levelSaved');

$cct = $params['cct'] ?? null;
$cctSaved = $this->getProperty('cctSaved');

$sceneName = $params['sceneName'] ?? null;
$sceneNameSaved = $this->getProperty('sceneNameSaved');

$autoMode = ($params['autoMode'] ?? 0) == 1;
$mode = $this->getProperty('mode') ?? '2';

// --- Обычный режим (без авто)
if (!$autoMode) {
  if($mode == 1){
    $this->setProperty('color', $color ?? $colorSaved ?? '#FFFFFF', 'noAutoMode');
    $this->setProperty('colorLevel', $colorLevel ?? $colorLevelSaved ?? 100, 'noAutoMode');
  }elseif($mode == 2){
    $this->setProperty('level', $level ?? $levelSaved ?? 100, 'noAutoMode');
    $this->setProperty('cct', $cct ?? $cctSaved ?? 100, 'noAutoMode');
  }elseif($mode == 3){
    $this->setProperty('sceneName', $sceneName ?? $sceneNameSaved ?? 'Спокойная', 'noAutoMode');
  }
}

// --- Авто режим 
if ($autoMode && !$this->getProperty('flag')) {
  $levels = getAutoLevelCct($this, $level, $cct, $color, $colorLevel, $sceneName);
  if($levels['level'] !== null && $levels['cct'] !== null && $levels['color'] !== null && $levels['colorLevel'] !== null && $levels['sceneName'] !== null && $levels['dayNightMode'] !== null){
    if($levels['dayNightMode'] == 1){
      $this->setProperty('color', $levels['color'], 'autoMode');
      $this->setProperty('colorLevel', $levels['colorLevel'], 'autoMode');
    }elseif($levels['dayNightMode'] == 2){
      $this->setProperty('level', $levels['level'], 'autoMode');
      $this->setProperty('cct', $levels['cct'], 'autoMode');
    }elseif($levels['dayNightMode'] == 3){
      $this->setProperty('sceneName', $levels['sceneName'], 'autoMode');
    }
    // --- Авто-выключение
	if ((int)$this->getProperty('timerOff') > 0)
		autoOff($this);
  }
}

