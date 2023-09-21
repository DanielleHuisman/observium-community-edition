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

$disk = dbFetchRow("SELECT * FROM `ucd_diskio` WHERE `diskio_id` = ?", [$vars['id']]);

if (is_numeric($disk['device_id']) && ($auth || device_permitted($disk['device_id']))) {
    $device = device_by_id_cache($disk['device_id']);

    $rrd_filename = get_rrd_path($device, "ucd_diskio-" . $disk['diskio_descr'] . ".rrd");

    $auth  = TRUE;

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: Disk :: " . $disk['diskio_descr'];
}

// EOF
