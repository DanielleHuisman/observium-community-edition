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

$data = dbFetchRow("SELECT * FROM `eigrp_ports` WHERE `eigrp_port_id` = ?", [$vars['id']]);

if (is_numeric($data['device_id']) && ($auth || device_permitted($data['device_id']))) {
    $device = device_by_id_cache($data['device_id']);
    $port   = get_port_by_id_cache($data['port_id']);

    $rrd_filename = get_rrd_path($device, "eigrp_port-" . $data['eigrp_vpn'] . "-" . $data['eigrp_as'] . "-" . $port['ifIndex'] . ".rrd");

    $auth  = TRUE;

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: EIGRP Port :: " . $port['port_label_short'];
}

// EOF
