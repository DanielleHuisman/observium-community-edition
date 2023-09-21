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

if (safe_empty($vars['id'])) {
    return;
}

$sql       = 'SELECT * FROM `wifi_aps_members` LEFT JOIN `wifi_aps` USING (`ap_name`) WHERE `ap_index_member` = ?';
$ap_member = dbFetchRow($sql, [$vars['id']]);

if (is_numeric($ap_member['device_id']) && ($auth || device_permitted($ap_member['device_id']))) {
    $device = device_by_id_cache($ap_member['device_id']);

    $rrd_filename = get_rrd_path($device, "lwappmember-" . $ap_member['ap_index_member']);
    $auth         = TRUE;

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: WiFi Accesspoint :: " . $ap_member['ap_name'];
}

// EOF
