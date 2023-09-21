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

$rserver = dbFetchRow("SELECT * FROM `loadbalancer_rservers` WHERE `rserver_id` = ?", [$vars['id']]);

if (is_numeric($rserver['device_id']) && ($auth || device_permitted($rserver['device_id']))) {
    $device = device_by_id_cache($rserver['device_id']);
    if ($rserver['state']) {
        $rserver = array_merge($rserver, safe_json_decode($rserver['state']));
    }

    $rrd_filename = get_rrd_path($device, "rserver-" . $rserver['rserver_id'] . ".rrd");

    $auth  = TRUE;

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: Rserver :: " . $rserver['ServerFarmName'] . ' - ' . $rserver['Name'];
}

// EOF
