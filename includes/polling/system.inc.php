<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

unset($cache['devices']['uptime'][$device['device_id']]);

$poll_device = [];

$include_order = 'default';                  // Default MIBs first (not sure, need more use cases)
$include_dir   = "includes/polling/system";
include("includes/include-dir-mib.inc.php");

// 5. Always keep SNMPv2-MIB::sysUpTime.0 as last point for uptime
$uptimes = ['use' => 'sysUpTime', 'sysUpTime' => $poll_device['sysUpTime']];

// Find MIB-specific SNMP data via OID fetch: sysDescr, sysLocation, sysContact, sysName, sysUpTime
$system_metatypes = ['sysDescr', 'sysLocation', 'sysContact', 'sysName', 'sysUpTime', 'reboot']; // 'snmpEngineID' ];
poll_device_mib_metatypes($device, $system_metatypes, $poll_device);

// Store original sysName for devices who store hardware in this Oid,
$poll_device['sysName_original'] = $poll_device['sysName'];
$poll_device['sysName']          = strtolower($poll_device['sysName']);
print_debug_vars($poll_device);

// If polled time not set by MIB include, set to unixtime
if (!isset($polled)) {
    $polled = time();
}

// Uptime data and reboot triggers
// NOTES. http://net-snmp.sourceforge.net/docs/FAQ.html#The_system_uptime__sysUpTime__returned_is_wrong_
// According to it, sysUptime reports time since last snmpd restart, while 
// hrSystemUptime reports time since last system reboot.
// And prefer snmpEngineTime over hrSystemUptime and sysUptime, since they limited with 497 days

// 5. As last point used sysUptime (see above)

if (isset($agent_data['uptime'])) {
    $agent_data['uptime']  = explode(' ', $agent_data['uptime'])[0];
    $uptimes['unix-agent'] = round((float)$agent_data['uptime']);
} elseif (isset($wmi['uptime'])) {
    $uptimes['wmi'] = (int)$wmi['uptime'];
}

if (is_numeric($agent_data['uptime']) && $agent_data['uptime'] > 0) {
    // 1. Unix-agent uptime is highest priority, since mostly accurate
    $uptimes['use']     = 'unix-agent';
    $uptimes['message'] = 'Using UNIX Agent Uptime';
} elseif (is_numeric($wmi['uptime']) && $wmi['uptime'] > 0) {
    // 1. WMI uptime is highest priority, since mostly accurate (for Windows)
    $uptimes['use']     = 'wmi';
    $uptimes['message'] = 'Using WMI Uptime';
} elseif (isset($poll_device['device_uptime']) && is_numeric($poll_device['device_uptime']) && $poll_device['device_uptime'] > 0) {
    // 2. Uptime from os specific OID, see in includes/polling/system MIB specific
    // Get uptime by some custom way in device os poller, see example in wowza-engine os poller
    $uptimes['device_uptime'] = round((float)$poll_device['device_uptime']);
    $uptimes['use']           = 'device_uptime';
    $uptimes['message']       = $uptimes['message'] ?: 'Using device MIB poller Uptime';
} else {
    // 3. Uptime from hrSystemUptime (only in snmp v2c/v3)
    // NOTE, about windows uptime,
    // sysUpTime resets when SNMP service restarted, but hrSystemUptime resets at 49.7 days (always),
    // Now we use LanMgr-Mib-II-MIB::comStatStart.0 as reboot time instead
    if ($device['os'] !== 'windows' &&
        $device['snmp_version'] !== 'v1' && is_device_mib($device, 'HOST-RESOURCES-MIB')) {
        // HOST-RESOURCES-MIB::hrSystemUptime.0 = Wrong Type (should be Timeticks): 1632295600
        // HOST-RESOURCES-MIB::hrSystemUptime.0 = Timeticks: (63050465) 7 days, 7:08:24.65
        $hrSystemUptime            = snmp_get_oid($device, 'hrSystemUptime.0', 'HOST-RESOURCES-MIB');
        $uptimes['hrSystemUptime'] = timeticks_to_sec($hrSystemUptime);

        if (is_numeric($uptimes['hrSystemUptime']) && $uptimes['hrSystemUptime'] > 0) {
            // hrSystemUptime always prefer if not zero
            $uptimes['use'] = 'hrSystemUptime';
        }
    }

    // 4. Uptime from snmpEngineTime (only in snmp v2c/v3)
    if ($device['snmp_version'] !== 'v1' && is_device_mib($device, 'SNMP-FRAMEWORK-MIB')) {
        $snmpEngineTime = snmp_get_oid($device, 'snmpEngineTime.0', 'SNMP-FRAMEWORK-MIB');

        if (is_numeric($snmpEngineTime) && $snmpEngineTime > 0) {
            if ($device['os'] === 'aos' && strlen($snmpEngineTime) > 8) {
                // Some Alcatel have bug with snmpEngineTime
                // See: https://jira.observium.org/browse/OBSERVIUM-763
                print_debug("snmpEngineTime $snmpEngineTime was reset to 0 due an Alcatel AOS bug.");
                $snmpEngineTime = 0;
            } elseif ($device['os'] === 'ironware') {
                // Check if version correct like "07.4.00fT7f3"
                $ironware_version = explode('.', $device['version']);
                if (count($ironware_version) > 2 && $ironware_version[0] > 0 &&
                    version_compare($device['version'], '5.1.0') === -1) {
                    // IronWare before Release 05.1.00b have bug (firmware returning snmpEngineTime * 10)
                    // See: https://jira.observium.org/browse/OBSERVIUM-1199
                    print_debug("snmpEngineTime $snmpEngineTime was divided by 10 due an IronWare issue.");
                    $snmpEngineTime /= 10;
                }
            } elseif ($snmpEngineTime > get_time('1day')) {
                // Device reports current unixtime instead of uptime
                print_debug("snmpEngineTime $snmpEngineTime was reset to 0 due seems as current Unixtime.");
                $snmpEngineTime = 0;
            }
            $uptimes['snmpEngineTime'] = (int)$snmpEngineTime;

            if ($uptimes['use'] === 'hrSystemUptime') {
                // Prefer snmpEngineTime only if more than hrSystemUptime
                if ($uptimes['snmpEngineTime'] > $uptimes['hrSystemUptime']) {
                    $uptimes['use'] = 'snmpEngineTime';
                }
            } elseif ($uptimes['snmpEngineTime'] >= $uptimes['sysUpTime']) {
                // in other cases prefer if more than sysUpTime
                $uptimes['use'] = 'snmpEngineTime';
            }
        }
    }

}

$uptimes['uptime']    = $uptimes[$uptimes['use']];         // Get actual uptime based on use flag
$uptimes['formatted'] = format_uptime($uptimes['uptime']); // Human readable uptime
if (!isset($uptimes['message'])) {
    $uptimes['message'] = 'Using SNMP Agent ' . $uptimes['use'];
}

$uptime = $uptimes['uptime'];
print_debug($uptimes['message'] . " ($uptime sec. => " . $uptimes['formatted'] . ')');

if (is_numeric($uptime) && $uptime > 0) {                  // it really is very impossible case for $uptime equals to zero
    $uptimes['previous'] = $device['uptime'];              // Uptime from previous device poll
    $uptimes['diff']     = $uptimes['previous'] - $uptime; // Difference between previous and current uptimes

    // Calculate current last rebooted time
    $last_rebooted = $polled - $uptime;
    // Previous reboot unixtime
    $uptimes['last_rebooted'] = $device['last_rebooted'];
    if (empty($uptimes['last_rebooted']) ||                      // 0 or ''
        abs($device['last_rebooted'] - $last_rebooted) > 1200) { // Fix when uptime updated by other Oid
        // Set last_rebooted for all devices who not have it already
        $uptimes['last_rebooted']      = $last_rebooted;
        $update_array['last_rebooted'] = $last_rebooted;
    }

    // Notify only if current uptime less than one week (eg if changed from sysUpTime to snmpEngineTime)
    if (device_rebooted($device, $uptimes)) {
        $update_array['last_rebooted'] = $polled - $uptime;                        // Update last reboot unixtime
        log_event('Device rebooted: after ' . format_uptime($polled - $uptimes['last_rebooted']) .
                  ' (Uptime: ' . $uptimes['formatted'] . ', previous: ' . format_uptime($uptimes['previous']) .
                  ', used: ' . $uptimes['use'] . ')', $device, 'device', $device['device_id'], 4);
        $uptimes['last_rebooted'] = $update_array['last_rebooted'];                // store new reboot unixtime
    }

    rrdtool_update_ng($device, 'uptime', [ 'uptime' => $uptime ]);

    $graphs['uptime'] = TRUE;

    print_cli_data('Uptime', $uptimes['formatted']);
    print_cli_data('Last reboot', format_unixtime($uptimes['last_rebooted']));

    $update_array['uptime']                                        = $uptime;
    $cache['devices']['uptime'][$device['device_id']]['uptime']    = $uptime;
    $cache['devices']['uptime'][$device['device_id']]['sysUpTime'] = $uptimes['sysUpTime']; // Required for ports (ifLastChange)
    $cache['devices']['uptime'][$device['device_id']]['polled']    = $polled;
} else {
    $uptimes['rebooted'] = 0;
    print_warning('Device does not have any uptime counter or uptime equals zero.');
}
$rebooted = $uptimes['rebooted']; // Keep rebooted var for alerts
print_debug_vars($uptimes, 1);

// Poll additional device metrics by mib definitions
foreach (get_device_mibs_permitted($device) as $mib) {
    // Load Average
    if (poll_device_mib_la($device, $mib, $device_state)) {
        break; // Stop walking other MIBs
    }

}

// Check if many oids empty (seems as device down after check)
$poll_empty_count = 0;

// Rewrite sysLocation if there is a mapping array or DB override
//$poll_device['sysLocation'] = snmp_fix_string($poll_device['sysLocation']);
$poll_device['sysLocation'] = rewrite_location($poll_device['sysLocation']);

if ($device['location'] != $poll_device['sysLocation']) {
    $update_array['location'] = $poll_device['sysLocation'];
    log_event("sysLocation changed: '" . $device['location'] . "' -> '" . $poll_device['sysLocation'] . "'", $device, 'device', $device['device_id']);

    if (strlen($poll_device['sysLocation']) === 0) {
        $poll_empty_count++;
    } else {
        $poll_empty_count--;
    }
}

$poll_device['sysContact'] = str_replace(['\"', '"'], '', $poll_device['sysContact']);

if (!is_valid_param($poll_device['sysContact'], 'sysContact')) {
    // Common wrong contacts: Uninitialized, not set, <none>, (none), SNMPv2, Unknown, ?, <private>
    $poll_device['sysContact'] = '';
}

print_debug_vars($poll_device);

// Check if snmpEngineID changed
$force_discovery = FALSE;

if ($poll_device['snmpEngineID'] != $device['snmpEngineID']) {
    $update_array['snmpEngineID'] = (string)$poll_device['snmpEngineID'];

    if (!safe_empty($device['snmpEngineID']) && !safe_empty($poll_device['snmpEngineID'])) {
        // snmpEngineID changed, force full device rediscovery
        log_event('snmpEngineID changed: ' . $device['snmpEngineID'] . ' -> ' . $poll_device['snmpEngineID'] . ' (probably the device was replaced). The device will be rediscovered.', $device, 'device', $device['device_id'], 4);
        $force_discovery = TRUE;
        force_discovery($device);
    } else {
        log_event('snmpEngineID -> ' . $poll_device['snmpEngineID'], $device, 'device', $device['device_id']);
    }

    if (safe_empty($poll_device['snmpEngineID'])) {
        $poll_empty_count++;
    } else {
        $poll_empty_count--;
    }
}

$oids = ['sysObjectID', 'sysContact', 'sysName', 'sysDescr'];
foreach ($oids as $oid) {
    $poll_device[$oid] = snmp_fix_string($poll_device[$oid]);
    //print_vars($poll_device[$oid]);
    if ($poll_device[$oid] != $device[$oid]) {
        $update_array[$oid] = $poll_device[$oid] ?: ['NULL'];
        log_event("$oid -> '" . $poll_device[$oid] . "'", $device, 'device', $device['device_id']);

        if (safe_empty($poll_device[$oid])) {
            $poll_empty_count++;
        } else {
            $poll_empty_count--;
        }

        // Update $device array for cases when model specific options required
        if ($oid === 'sysObjectID' && $poll_device['sysObjectID']) {
            $device['sysObjectID'] = $poll_device['sysObjectID'];
        }
    }
}

// Re-check device status
if ($poll_empty_count >= 2) {
    $device_status = device_status_array($device);
    if ($device['status'] != $device_status['status'] &&
        $device_status['status'] == 0) {
        $status                = $device_status['status'];
        $status_type           = $device_status['status_type'];
        $device['status']      = $device_status['status'];
        $device['status_type'] = $device_status['status_type'];

        // device going down by some reasons, prevent other polling
        // reset update array
        $update_array = [];
        return;
    }
}

// Check if both (sysObjectID and sysDescr) changed, force full device rediscovery
if (!$force_discovery && // Check if not already forced
    isset($update_array['sysObjectID'], $update_array['sysDescr']) &&
    strlen($poll_device['sysObjectID']) && strlen($device['sysObjectID']) &&
    strlen($poll_device['sysDescr']) && strlen($device['sysDescr'])) {
    log_event('sysObjectID and sysDescr changed: seems the device was replaced. The device will be rediscovered.', $device, 'device', $device['device_id'], 4);
    force_discovery($device);
}

if (!empty($device['snmpable'])) {
    print_cli_data('SNMPable OID', $device['snmpable'], 2);
}
print_cli_data('sysObjectID', $poll_device['sysObjectID'], 2);
print_cli_data('snmpEngineID', $poll_device['snmpEngineID'], 2);
print_cli_data('sysDescr', $poll_device['sysDescr'], 2);
print_cli_data('sysName', $poll_device['sysName_original'], 2);
print_cli_data('Location', $poll_device['sysLocation'], 2);

// Restore original (not lower case) sysName
if (isset($poll_device['sysName_SNMPv2'])) {
    $poll_device['sysName'] = $poll_device['sysName_SNMPv2'];
    unset($poll_device['sysName_SNMPv2']);
} else {
    $poll_device['sysName'] = $poll_device['sysName_original'];
}
unset($poll_device['sysName_original']);

// Geolocation detect
if ($config['geocoding']['enable']) {

    $geo_db = dbFetchRow('SELECT *, UNIX_TIMESTAMP(`location_updated`) AS `location_unixtime` FROM `devices_locations` WHERE `device_id` = ?', [ $device['device_id'] ]);
    if (!$geo_db) { $geo_db = []; }
    $geo_db['hostname'] = $device['hostname']; // Hostname required for detect by DNS
    print_debug_vars($geo_db);

    $geo_detect = geo_detect($device, $poll_device, $geo_db, $dns_only);

    if ($geo_detect || $dns_only) {
        $update_geo = get_geolocation($poll_device['sysLocation'], $geo_db, $dns_only);
        if ($update_geo) {
            print_debug_vars($update_geo, 1);
            if (is_numeric($update_geo['location_lat']) && is_numeric($update_geo['location_lon']) && $update_geo['location_country'] !== 'Unknown') {
                $geo_msg = 'Geolocation (' . strtoupper($update_geo['location_geoapi']) . ') -> ';
                $geo_msg .= '[' . sprintf('%f', $update_geo['location_lat']) . ', ' . sprintf('%f', $update_geo['location_lon']) . '] ';
                $geo_msg .= $update_geo['location_country'] . ' (Country), ' . $update_geo['location_state'] . ' (State), ';
                $geo_msg .= $update_geo['location_county'] . ' (County), ' . $update_geo['location_city'] . ' (City)';
            } else {
                $geo_msg = FALSE;
            }

            if (is_numeric($geo_db['location_id'])) {
                foreach ($update_geo as $k => $value) {
                    if ($geo_db[$k] == $value) {
                        unset($update_geo[$k]);
                    }
                }
                if ($update_geo) {
                    dbUpdate($update_geo, 'devices_locations', '`location_id` = ?', [$geo_db['location_id']]);
                    if ($geo_msg) {
                        log_event($geo_msg, $device, 'device', $device['device_id']);
                    }
                } // else not changed
            } else {
                $update_geo['device_id'] = $device['device_id'];
                dbInsert($update_geo, 'devices_locations');
                if ($geo_msg) {
                    log_event($geo_msg, $device, 'device', $device['device_id']);
                }
            }

        } elseif (is_numeric($geo_db['location_id'])) {
            $update_geo = ['location_updated' => format_unixtime(get_time(), 'Y-m-d G:i:s')]; // Increase updated time
            dbUpdate($update_geo, 'devices_locations', '`location_id` = ?', [$geo_db['location_id']]);
        } # end if $update_geo
    }
}

unset($geo_detect, $dns_only, $geo_db, $update_geo);

// EOF
