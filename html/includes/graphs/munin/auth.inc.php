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

if (is_numeric($vars['plugin'])) {
    $mplug = dbFetchRow("SELECT * FROM `munin_plugins` WHERE `mplug_id` = ?", [$vars['plugin']]);
} else {
    $mplug = dbFetchRow("SELECT * FROM `munin_plugins` WHERE `device_id` = ? AND `mplug_type` = ?", [$device['device_id'], $vars['plugin']]);
}

if (is_numeric($mplug['device_id']) && ($auth || device_permitted($mplug['device_id']))) {
    $device   = device_by_id_cache($mplug['device_id']);

    $plugfile = get_rrd_path($device, "munin/" . $mplug['mplug_type']);

    $auth = TRUE;

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: Plugin :: " . $mplug['mplug_type'] . " - " . $mplug['mplug_title'];
}

// EOF
