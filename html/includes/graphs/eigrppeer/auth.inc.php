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

$data = dbFetchRow("SELECT * FROM `eigrp_peers` WHERE `eigrp_peer_id` = ?", [$vars['id']]);

if (is_numeric($data['device_id']) && ($auth || device_permitted($data['device_id']))) {
    $device = device_by_id_cache($data['device_id']);

    $rrd_filename = get_rrd_path($device, "eigrp_peer-" . $data['eigrp_vpn'] . "-" . $data['eigrp_as'] . "-" . $data['peer_addr'] . ".rrd");

    $auth  = TRUE;

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: EIGRP Peer :: " . $data['peer_addr'];
}

// EOF
