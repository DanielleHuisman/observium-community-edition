<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!is_intnum($vars['id'])) {
    return;
}

$sensor = get_entity_by_id_cache('sensor', $vars['id']);

if (is_numeric($sensor['device_id']) && ($auth || is_entity_permitted($sensor['sensor_id'], 'sensor') || device_permitted($sensor['device_id']))) {

    $device = device_by_id_cache($sensor['device_id']);

    $rrd_filename = get_rrd_path($device, get_sensor_rrd($device, $sensor));

    $auth  = TRUE;

    $title_array   = [];
    $title_array[] = [ 'text' => $device['hostname'],
                       'url'  => generate_url([ 'page' => 'device', 'device' => $device['device_id'] ]) ];
    $title_array[] = [ 'text' => 'Sensors ('.nicecase($sensor['sensor_class']).')',
                       'url'  => generate_url([ 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => $sensor['sensor_class'] ]) ];
    $title_array[] = [ 'text' => escape_html($sensor['status_descr']),
                       'url'  => generate_url([ 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => $sensor['sensor_class'], 'sensor_id' => $vars['id'] ]) ];


    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: Sensor :: " . $sensor['sensor_descr'];
}

// EOF
