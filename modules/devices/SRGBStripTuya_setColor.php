<?php
/*
Установиь цвет в (array("value"=> '#------'))
вместо '#------' можно отправлять пресеты
    'red'
    'green'
    'blue'
    'white'
    'yellow'
    'cyan'
    'magenta'
    'orange'
    'purple'
    'pink'
    'lime'
*/
if (!isset($params['color']) && !isset($params['value'])) return;

$this->setProperty('color', $params['color'] ?? $params['value']);
