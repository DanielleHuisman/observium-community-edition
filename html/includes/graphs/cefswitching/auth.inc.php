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

$cef = dbFetchRow("SELECT * FROM `cef_switching` WHERE `cef_switching_id` = ?", [$vars['id']]);

if (is_numeric($cef['device_id']) && ($auth || device_permitted($cef['device_id']))) {
    $device = device_by_id_cache($cef['device_id']);

    $rrd_filename = get_rrd_path($device, "cefswitching-" . $cef['entPhysicalIndex'] . "-" . $cef['afi'] . "-" . $cef['cef_index'] . ".rrd");

    $auth  = TRUE;

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: CEF Switching :: " . $cef['cef_descr'];
}

// EOF
