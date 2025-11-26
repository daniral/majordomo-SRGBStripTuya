<?php
/**
 * Обрабатывает голосовые команды для устройства типа SRGBStripTuya2.
 *
 * Функция анализирует текст голосовой команды и формирует код выполнения ($run_code)
 * для объекта ленты SRGBStripTuya2. Поддерживаются включение, выключение, переключение,
 * управление яркостью, управлением цветом и сценами.
 *
 * Поддерживаемые возможности:
 *
 * 1. Управление питанием:
 *    - Включение устройства (по паттерну LANG_DEVICES_PATTERN_TURNON)
 *    - Выключение устройства (LANG_DEVICES_PATTERN_TURNOFF)
 *    - Переключение состояния (LANG_DEVICES_PATTERN_SWITCH)
 *
 * 2. Управление яркостью:
 *    2.1. Установка абсолютного значения:
 *         - Команды вида: "яркость 70%", "сделай на 30", "поставь 100"
 *         - Распознаются числа 1–100
 *    2.2. Повышение яркости:
 *         - Ключевые слова: "ярче", "увеличь", "прибавь", "добавь", "больше"
 *         - Увеличивает текущий уровень на +10
 *    2.3. Понижение яркости:
 *         - Ключевые слова: "тусклее", "меньше", "приглуши", "потемнее"
 *         - Уменьшает текущий уровень на -10
 *
 * 3. Управление цветом:
 *    - Используются ключевые слова из словаря LANG_SRGBStripTuya2_PATTERN_COLOR
 *    - Поддерживаемые цвета: красный, зелёный, синий, белый, жёлтый, голубой,
 *      пурпурный, оранжевый, фиолетовый, розовый, лайм.
 *    - Команда вызывает метод setColor(value => <цвет>)
 *    - Автоматически сохраняет предыдущий цвет для отката ($opposite_code)
 *
 * 4. Управление сценами:
 *    - Имена сцен берутся из свойства scenesList (формат: Имя=Значение,Имя=Значение,...)
 *    - Команда вызывает установку имени сцены в свойство sceneName
 *    - Автоматически сохраняется предыдущая сцена для отката
 *
 * Результат работы:
 *    - Устанавливает $run_code — код действия
 *    - Устанавливает $opposite_code — код отмены (если возможно)
 *    - Устанавливает $processed = 1 при успешном распознавании команды
 *    - При необходимости устанавливает $reply_confirm = 1
 *
 * Ожидаемые входные параметры (передаются извне в область видимости):
 *    @param string $device_type     Тип устройства (должен быть 'SRGBStripTuya2')
 *    @param string $command         Текст голосовой команды пользователя
 *    @param string $linked_object   Имя объекта в MajorDoMo
 *    @param string $device_title    Человекочитаемое название устройства
 *    @param string $add_phrase      Дополнительная фраза для TTS ("в комнате", "на кухне")
 *
 * Используемые глобальные переменные (по ссылке):
 *    @var string $run_code          Формируемый код выполнения
 *    @var string $opposite_code     Формируемый код обратного действия
 *    @var int    $processed         Флаг успешной обработки команды
 *    @var int    $reply_confirm     Флаг необходимости голосового подтверждения
 *
 * @return void
 */

if ($device_type == 'SRGBStripTuya2') {

    // --- ВКЛ / ВЫКЛ / ПЕРЕКЛЮЧИТЬ ---
    if (preg_match('/' . LANG_DEVICES_PATTERN_TURNON . '/uis', $command)) {
        sayReplySafe(LANG_TURNING_ON . ' ' . $device_title . $add_phrase, 2);
        $run_code      .= "callMethod('$linked_object.turnOn');";
        $opposite_code .= "callMethod('$linked_object.turnOff');";
        $processed = 1;
    }
    elseif (preg_match('/' . LANG_DEVICES_PATTERN_TURNOFF . '/uis', $command)) {
        sayReplySafe(LANG_TURNING_OFF . ' ' . $device_title . $add_phrase, 2);
        $run_code      .= "callMethod('$linked_object.turnOff');";
        $opposite_code .= "callMethod('$linked_object.turnOn');";
        $processed = 1;
    }
    elseif (preg_match('/' . LANG_DEVICES_PATTERN_SWITCH . '/uis', $command)) {
        sayReplySafe(LANG_SWITCH . ' ' . $device_title . $add_phrase, 2);
        $run_code      .= "callMethod('$linked_object.switch');";
        $opposite_code .= "callMethod('$linked_object.switch');";
        $processed = 1;
    }

    // --- ЯРКОСТЬ ---
    elseif (preg_match('/' . LANG_SRGBStripTuya2_PATTERN_BRIGHTNESS . '/uis', $command)) {
        $currentLevel = (int)getGlobal("$linked_object.level");
        $step = 10;
        if (preg_match('/(?:\s)(\d{1,2}|100)(?:%|\s|$)/uis', $command, $matches)) {
            $value = (int)$matches[1];
        }
        elseif (preg_match('/(ярче|увелич|добав|больше)/uis', $command)) {
            $value = min(100, $currentLevel + $step);
        }
        elseif (preg_match('/(тусклее|меньше|приглуш|потемн)/uis', $command)) {
            $value = max(0, $currentLevel - $step);
        }
        if (isset($value)) {
            $run_code      .= "callMethod('$linked_object.setLevel', array('value' => $value));";
            $opposite_code .= "callMethod('$linked_object.setLevel', array('value' => $value));";
            $processed = 1;
            $reply_confirm = 1;
        }
    }

    // --- ЦВЕТ ---
    elseif (preg_match('/' . LANG_SRGBStripTuya2_PATTERN_COLOR . '/uis', $command)) {
        $colors = array(
            'красн' => 'red', 'зел' => 'green', 'син' => 'blue',
            'бел' => 'white', 'жёлт' => 'yellow', 'желт' => 'yellow',
            'голуб' => 'cyan', 'пурпур' => 'purple', 'оранж' => 'orange',
            'фиолет' => 'violet', 'розов' => 'pink', 'лайм' => 'lime'
        );
        $newColor = null;
        foreach ($colors as $pattern => $clr) {
            if (preg_match('/' . $pattern . '/uis', $command)) {
                $newColor = $clr;
                break;
            }
        }
        if ($newColor) {
            $prevColor = getGlobal("$linked_object.color");
            $run_code      .= "callMethod('$linked_object.setColor', array('value' => '$newColor'));" ;
            if ($prevColor) $opposite_code .= "callMethod('$linked_object.setColor', array('value' => '$prevColor'));" ;
            $processed = 1;
            $reply_confirm = 1;
        }
    }

    // --- СЦЕНЫ ---
    elseif (preg_match('/' . LANG_SRGBStripTuya2_PATTERN_SCENE . '/uis', $command)) {
        $scenesRaw = getGlobal("$linked_object.scenesList");
        if ($scenesRaw) {
            $sceneItems = preg_split('/\s*(?:,|\r\n|\n|\r)\s*/', $scenesRaw, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($sceneItems as $item) {
                $parts = explode('=', $item, 2);
                if (count($parts) == 2) {
                    $sceneName = trim($parts[0]);
                    if (preg_match('/' . preg_quote($sceneName, '/') . '/uis', $command)) {
                        $prevScene = getGlobal("$linked_object.sceneName");
                        $run_code .= "setProperty('$linked_object.sceneName', '$sceneName');";
                        $opposite_code .= "setProperty('$linked_object.sceneName', '$prevScene');";
                        $processed = 1;
                        $reply_confirm = 1;
                        break;
                    }
                }
            }
        }
    }
}
