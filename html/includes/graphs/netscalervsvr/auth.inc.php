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

$vsvr = dbFetchRow("SELECT * FROM `netscaler_vservers` WHERE `vsvr_id` = ?", [$vars['id']]);

if (is_numeric($vsvr['device_id']) && ($auth || device_permitted($vsvr['device_id']))) {
    $device = device_by_id_cache($vsvr['device_id']);

    $rrd_filename = get_rrd_path($device, "netscaler-vsvr-" . $vsvr['vsvr_name'] . ".rrd");

    $auth = TRUE;

    $title_array   = [];
    $title_array[] = ['text' => $device['hostname'], 'url' => generate_url(['page' => 'device', 'device' => $device['device_id']])];
    $title_array[] = ['text' => 'Netscaler vServer', 'url' => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'loadbalancer', 'type' => 'netscaler_vsvr'])];
    $title_array[] = ['text' => $vsvr['vsvr_name'], 'url' => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'loadbalancer', 'type' => 'netscaler_vsvr', 'vsvr' => $vsvr['vsvr_id']])];

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: Netscaler VServer :: " . $vsvr['vsvr_name'];
}

// EOF
