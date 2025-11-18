<?php
/**
 * normalizeRange($val, $min, $max) — Проверяет и нормализует значение (число или HEX) в заданный диапазон.
 * adjustProperty($obj, $property, $value, $direction, $defaultStep, $min, $max) - Универсальное изменение свойства (увеличить/уменьшить)
 * hsvToRgbHex($hsvHex) - Конвертирует 12-значный Tuya HSV в RGB HEX и яркость.
 * rgbToHSVhex($rgbHex, $brightness) - Конвертирует RGB HEX + яркость в Tuya HSV (12 hex цифр).
 * 
 *--------------------------------------------------------------------------------
 *|      Функция       |          Назначение                                     |
 *| ------------------ | ------------------------------------------------------- |
 *| `normalizeRange`   | Нормализует число или HEX в диапазон                    |
 *| `adjustProperty`   | Универсальное изменение свойства (яркость/температура)  |
 *| `hsvToRgbHex`      | Конвертирует 12-значный Tuya HSV в RGB HEX и яркость    |
 *| `rgbToHSVhex`      | Конвертирует RGB HEX + яркость в Tuya HSV               |
 *--------------------------------------------------------------------------------
 *///

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

