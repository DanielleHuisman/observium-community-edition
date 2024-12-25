<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) Adam Armstrong
 *
 */

// Redetect OS if necessary (controlled by discover_device function)
if ($detect_os && check_device_os_changed($device)) {
    $device['type'] = $config['os'][$device['os']]['type'] ?? 'unknown'; // Also change $type

    // Set device sysObjectID when device os changed
    $sysObjectID = snmp_cache_sysObjectID($device);
    if ($device['sysObjectID'] != $sysObjectID) {
        dbUpdate(['sysObjectID' => $sysObjectID], 'devices', '`device_id` = ?', [ $device['device_id'] ]);
        $device['sysObjectID'] = $sysObjectID;
    }
}

// EOF
