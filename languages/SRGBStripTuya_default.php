<?php

/**
 * ============================================================
 * Dictionary for SRGBStripTuya control
 * ============================================================
 *
 * $dictionary - array defining patterns to recognize commands:
 *   - 'SRGBStripTuya_PATTERN_BRIGHTNESS': keywords for brightness control
 *   - 'SRGBStripTuya_PATTERN_COLOR': keywords for color control
 *
 * Each value is a string with keywords separated by |
 * Constants with the LANG_ prefix are defined for each key
 *   e.g., LANG_SRGBStripTuya_PATTERN_BRIGHTNESS
 * These constants are used to recognize text or voice commands.
 */
$dictionary = array(

    // Brightness control
    'SRGBStripTuya_PATTERN_BRIGHTNESS' => 'bright|brightness|lighter|dimmer|light level|increase light|decrease light',

    // Color control
    'SRGBStripTuya_PATTERN_COLOR' => 'red|green|blue|white|yellow|cyan|magenta|orange|purple|pink|lime',

    // Scene control
    'SRGBStripTuya_PATTERN_SCENE' => 'scene|mode|setting|'
);

foreach ($dictionary as $k => $v) {
    if (!defined('LANG_' . $k)) {
        @define('LANG_' . $k, $v);
    }
}
