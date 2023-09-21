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

$svc = dbFetchRow("SELECT * FROM `netscaler_services` WHERE `svc_id` = ?", [$vars['id']]);

if (is_numeric($svc['device_id']) && ($auth || device_permitted($svc['device_id']))) {
    $device = device_by_id_cache($svc['device_id']);

    $rrd_filename = get_rrd_path($device, "nscaler-svc-" . $svc['svc_name'] . ".rrd");

    $auth = TRUE;

    $title_array   = [];
    $title_array[] = [ 'text' => $device['hostname'],
                       'url'  => generate_url(['page' => 'device', 'device' => $device['device_id']])];
    $title_array[] = [ 'text' => 'Netscaler Service',
                       'url'  => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'loadbalancer', 'type' => 'netscaler_services'])];
    $title_array[] = [ 'text' => $svc['svc_label'],
                       'url'  => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'loadbalancer', 'type' => 'netscaler_services', 'svc' => $svc['svc_id']])];

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: Netscaler VServer :: " . $svc['svc_name'];
}

// EOF
