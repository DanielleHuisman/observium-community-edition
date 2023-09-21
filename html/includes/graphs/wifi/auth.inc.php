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

$radio = dbFetchRow("SELECT * FROM `wifi_accesspoints`, `wifi_radios` WHERE  `wifi_accesspoints`.`wifi_accesspoint_id` = `wifi_radios`.`accesspoint_id` AND `wifi_radios`.`wifi_radio_id` = ? ", [$vars['id']]);
if (is_numeric($radio['device_id']) && ($auth || device_permitted($radio['device_id']))) {
    $device = device_by_id_cache($radio['device_id']);

    $auth  = TRUE;

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: WiFi Accesspoint :: " . $radio['name'];
}

// EOF
