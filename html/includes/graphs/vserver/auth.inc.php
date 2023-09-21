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

$vserver = dbFetchRow("SELECT * FROM `lb_slb_vsvrs` AS WHERE `classmap_id` = ?", [$vars['id']]);

if (is_numeric($vserver['device_id']) && ($auth || device_permitted($vserver['device_id']))) {
    $device = device_by_id_cache($vserver['device_id']);

    $rrd_filename = get_rrd_path($device, "vserver-" . $vserver['classmap_index'] . ".rrd");

    $auth  = TRUE;

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: Serverfarm :: " . $vserver['classmap'];
}

// EOF
