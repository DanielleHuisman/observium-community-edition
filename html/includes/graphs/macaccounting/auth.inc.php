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

if (!is_intnum($vars['id'])) {
    return;
}

$ma = dbFetchRow("SELECT * FROM `mac_accounting` AS M, `ports` AS I, `devices` AS D WHERE M.ma_id = ? AND I.port_id = M.port_id AND I.device_id = D.device_id", [$vars['id']]);

if (is_numeric($ma['device_id']) && ($auth || port_permitted($ma['port_id']))) {

    $device       = device_by_id_cache($ma['device_id']);
    $rrd_filename = get_rrd_path($device, "mac_acc-" . $ma['ifIndex'] . "-" . $ma['vlan_id'] . "-" . $ma['mac'] . ".rrd");

    if (rrd_is_file($rrd_filename)) {
        $port   = get_port_by_id_cache($ma['port_id']);
        $device = device_by_id_cache($port['device_id']);

        $auth   = TRUE;

        $graph_title   = device_name($device, TRUE);
        $graph_title  .= " :: Port  " . $port['port_label_short'];
        $graph_title  .= " :: Mac Accounting";
        $graph_title  .= " :: " . format_mac($ma['mac']);
    }
}

// EOF
