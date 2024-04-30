<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if ($vars['metric'] !== 'sensors' && $metric !== 'sensors' && device_permitted($device)) {

    // Don't show aggregate graphs to people without device permissions, or for "all sensors" view.

    $graph_title           = nicecase($vars['metric']);
    $graph_array['type']   = "device_" . $vars['metric'];
    $graph_array['device'] = $device['device_id'];
    $graph_array['legend'] = 'no';

    $box_args = ['title' => $graph_title, 'header-border' => TRUE];

    echo generate_box_open($box_args);

    print_graph_row($graph_array);

    echo generate_box_close();

}

print_sensor_table($vars);

// EOF
