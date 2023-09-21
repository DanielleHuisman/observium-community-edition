<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (isset($config['sensor_types'][$subtype])) {
    $class = $subtype;
    $unit  = $config['sensor_types'][$subtype]['symbol'];
    if ($unit == '%') {
        $unit = '%%';
    }
    $unit_long = $config['sensor_types'][$subtype]['text'];

    include($config['html_dir'] . "/includes/graphs/device/sensor.inc.php");
} else {
//  graph_error($type.'_'.$subtype); // Graph Template Missing;
}

// EOL
