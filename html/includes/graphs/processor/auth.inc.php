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

$proc = get_entity_by_id_cache('processor', $vars['id']);

if (is_numeric($proc['device_id']) && ($auth || device_permitted($proc['device_id']))) {
    $device       = device_by_id_cache($proc['device_id']);
    $rrd_filename = get_rrd_path($device, get_processor_rrd($device, $proc));

    $title_array   = [];
    $title_array[] = [ 'text' => $device['hostname'],
                       'url'  => generate_url([ 'page' => 'device', 'device' => $device['device_id'] ]) ];
    $title_array[] = [ 'text' => 'Processors',
                       'url'  => generate_url([ 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => 'processor' ]) ];
    $title_array[] = [ 'text' => escape_html($proc['processor_descr']),
                       'url'  => generate_url([ 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => 'processor', 'processor_id' => $vars['id'] ]) ];

    $graph_title  = device_name($device, TRUE);
    $graph_title  .= " :: Processor :: " . rewrite_entity_name($proc['processor_descr'], 'processor');

    $auth         = TRUE;
}

// EOF
