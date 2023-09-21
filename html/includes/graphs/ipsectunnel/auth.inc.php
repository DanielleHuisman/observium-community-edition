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

$tunnel = dbFetchRow("SELECT * FROM `ipsec_tunnels` WHERE `tunnel_id` = ?", [$vars['id']]);

if (is_numeric($tunnel['device_id']) && ($auth || device_permitted($tunnel['device_id']))) {
    $device = device_by_id_cache($tunnel['device_id']);

    if ($tunnel['tunnel_endhash']) {
        // New index
        $rrd_index = $tunnel['local_addr'] . '-' . $tunnel['peer_addr'] . '-' . $tunnel['tunnel_endhash'];
    } else {
        $rrd_index = $tunnel['peer_addr'];
    }
    $rrd_filename = get_rrd_path($device, "ipsectunnel-" . $rrd_index . ".rrd");

    $auth  = TRUE;

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: IPSEC Tunnel :: " . $tunnel['peer_addr'];
}

// EOF
