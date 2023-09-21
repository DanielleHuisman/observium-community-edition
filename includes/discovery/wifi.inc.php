<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Build array of radios in the database

foreach (dbFetchRows("SELECT * FROM `wifi_radios` WHERE `device_id` = ?", [$device['device_id']]) as $radio) {
    $GLOBALS['cache']['wifi_radios'][$radio['radio_ap']][$radio['radio_number']] = $radio;
}

foreach (dbFetchRows("SELECT * FROM `wifi_wlans` WHERE `device_id` = ?", [$device['device_id']]) as $wlan) {
    $GLOBALS['cache']['wifi_wlans'][$wlan['wlan_index']] = $wlan;
}

foreach (dbFetchRows("SELECT * FROM `wifi_aps` WHERE `device_id` = ?", [$device['device_id']]) as $ap) {
    if (safe_empty($ap['ap_index'])) {
        // Clean broken entries
        dbDelete('wifi_aps', '`wifi_ap_id` =  ?', [$ap['wifi_ap_id']]);
        //dbDelete('wifi_aps_members', '`wifi_ap_id` =  ?', [ $ap['wifi_ap_id'] ]);
        continue;
    }
    $GLOBALS['cache']['wifi_aps'][$ap['ap_index']] = $ap;
}

// Include all discovery modules

$include_dir = 'includes/discovery/wifi';
include($config['install_dir'] . '/includes/include-dir-mib.inc.php');

// Remove non-valid things
print_debug_vars($GLOBALS['valid']['wifi']);

if (safe_count($GLOBALS['cache']['wifi_aps']) || safe_count($GLOBALS['valid']['wifi']['aps'])) {
    foreach ($GLOBALS['cache']['wifi_aps'] as $ap_index => $entry) {
        if (!isset($GLOBALS['valid']['wifi']['aps'][$ap_index])) {
            $wifi_ap_id = $entry['wifi_ap_id'];
            if ($entry['deleted'] || safe_empty($ap_index)) {
                echo("AP will delete AP:$ap_index with id:$wifi_ap_id");
                dbDelete('wifi_aps', '`wifi_ap_id` =  ?', [$wifi_ap_id]);
                dbDelete('wifi_aps_members', '`wifi_ap_id` =  ?', [$wifi_ap_id]);
            } else {
                //echo("AP don't exists in WLC anymore, but it's not marked to be deleted (considering Down): $ap_index with id:$wifi_ap_id\n");
                dbUpdate(['deleted' => 1], 'wifi_aps', '`device_id` = ? AND `wifi_ap_id` = ?', [$device['device_id'], $wifi_ap_id]);
            }
        }
    }
}

// FIXME - Actually write this code :)
// FIXME - No for real write this code. :D

unset($GLOBALS['cache']['wifi_radios'], $GLOBALS['cache']['wifi_wlans'], $GLOBALS['cache']['wifi_aps'], $ap, $wlan, $radio);

echo(PHP_EOL);

//EOF
