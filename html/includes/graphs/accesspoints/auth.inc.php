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

$ap = accesspoint_by_id($vars['id']);

if (is_numeric($ap['device_id']) && ($auth || device_permitted($ap['device_id']))) {
    $device = device_by_id_cache($ap['device_id']);

    $auth  = TRUE;

    $cleanmac = str_replace(':', '', $ap['mac_addr']);

    $rrd_filename = get_rrd_path($device, "arubaap-" . $cleanmac . "-" . $ap['radio_number'] . ".rrd");

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: AP :: " . $ap['name'];
}

// EOF
