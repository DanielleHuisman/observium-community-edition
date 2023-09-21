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

$status = dbFetchRow("SELECT * FROM `status` WHERE `status_id` = ?", [$vars['id']]);

if (is_numeric($status['device_id']) && ($auth || device_permitted($status['device_id']))) {
    $device = device_by_id_cache($status['device_id']);

    $auth  = TRUE;

    $rrd_filename = get_rrd_path($device, get_status_rrd($device, $status));

    $title_array   = [];
    $title_array[] = [ 'text' => $device['hostname'],
                       'url'  => generate_url([ 'page' => 'device', 'device' => $device['device_id'] ]) ];
    $title_array[] = [ 'text' => 'Statuses',
                       'url'  => generate_url([ 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => 'status' ]) ];
    $title_array[] = [ 'text' => escape_html($status['status_descr']),
                       'url'  => generate_url([ 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => 'status', 'status_id' => $vars['id'] ]) ];


    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: Status :: " . $status['status_descr'];
}

// EOF
