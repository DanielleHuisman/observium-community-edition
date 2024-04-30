<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Init collecting vars
$discovery_vlans       = [];
$discovery_ports_vlans = [];
$vlans_db              = [];
$ports_vlans_db        = [];
$ports_vlans           = [];

foreach (dbFetchRows("SELECT * FROM `vlans` WHERE `device_id` = ?", [ $device['device_id'] ]) as $vlan_db) {
    if (isset($vlans_db[$vlan_db['vlan_domain']][$vlan_db['vlan_vlan']])) {
        // Clean duplicates
        print_debug("Duplicate VLAN entry in DB found:");
        print_debug_vars($vlan_db);
        dbDelete('vlans', '`vlan_id` = ?', [$vlan_db['vlan_id']]);
        continue;
    }
    $vlans_db[$vlan_db['vlan_domain']][$vlan_db['vlan_vlan']] = $vlan_db;
}
print_debug_vars($vlans_db);

foreach (dbFetchRows("SELECT * FROM `ports_vlans` WHERE `device_id` = ?", [ $device['device_id'] ]) as $vlan_db) {
    if (isset($ports_vlans_db[$vlan_db['port_id']][$vlan_db['vlan']])) {
        // Clean duplicates
        print_debug("Duplicate Port VLAN entry in DB found:");
        print_debug_vars($vlan_db);
        dbDelete('ports_vlans', '`port_vlan_id` = ?', [$vlan_db['port_vlan_id']]);
        continue;
    }
    $ports_vlans_db[$vlan_db['port_id']][$vlan_db['vlan']] = $vlan_db;
}
print_debug_vars($ports_vlans_db);

// Include all discovery modules
$include_dir = "includes/discovery/vlans";
include("includes/include-dir-mib.inc.php");

print_debug_vars($discovery_vlans, 1);
print_debug_vars($discovery_ports_vlans, 1);

/* Process discovered Vlans */
$table_rows  = [];
$vlan_params = ['vlan_name', 'vlan_mtu', 'vlan_type', 'vlan_status', 'vlan_context', 'ifIndex'];
foreach ($discovery_vlans as $domain_index => $vlans) {
    foreach ($vlans as $vlan_id => $vlan) {
        echo(" $vlan_id");
        $vlan_update = [];

        // Currently, vlan_context param only actual for CISCO-VTP-MIB
        if (!isset($vlan['vlan_context'])) {
            $vlan['vlan_context'] = 0;
        }

        if (isset($vlans_db[$domain_index][$vlan_id])) {
            // Vlan already in db, compare
            foreach ($vlan_params as $param) {
                if ($vlans_db[$domain_index][$vlan_id][$param] != $vlan[$param]) {
                    if ($param === 'ifIndex' && (is_null($vlan[$param]) || $vlan[$param] === '')) {
                        // Empty string stored as 0, prevent
                        $vlan_update[$param] = ['NULL'];
                    } else {
                        $vlan_update[$param] = $vlan[$param];
                    }
                }
            }

            if (count($vlan_update)) {
                dbUpdate($vlan_update, 'vlans', 'vlan_id = ?', [$vlans_db[$domain_index][$vlan_id]['vlan_id']]);
                $module_stats[$vlan_id]['V'] = 'U';
                $GLOBALS['module_stats'][$module]['updated']++;
            } else {
                $module_stats[$vlan_id]['V'] = '.';
                $GLOBALS['module_stats'][$module]['unchanged']++;
            }

        } else {
            // New vlan discovered
            $vlan_update              = $vlan;
            $vlan_update['device_id'] = $device['device_id'];
            dbInsert($vlan_update, 'vlans');
            $module_stats[$vlan_id]['V'] = '+';
            $GLOBALS['module_stats'][$module]['added']++;
        }

        $table_rows[] = [$domain_index, $vlan_id, $vlan['vlan_name'], $vlan['vlan_type'], $vlan['vlan_status']];
    }
}
$table_headers = ['%WDomain%n', '%WVlan: ID%n', '%WName%n', '%WType%n', '%WStatus%n'];
print_cli_table($table_rows, $table_headers);
/* End process vlans */

// Clean removed vlans
foreach ($vlans_db as $domain_index => $vlans) {
    foreach ($vlans as $vlan_id => $vlan) {
        if (empty($discovery_vlans[$domain_index][$vlan_id])) {
            dbDelete('vlans', "`device_id` = ? AND vlan_domain = ? AND vlan_vlan = ?", [$device['device_id'], $domain_index, $vlan_id]);
            $module_stats[$vlan_id]['V'] = '-';
            $GLOBALS['module_stats'][$module]['deleted']++;

            $table_rows[] = [$domain_index, $vlan_id, $vlan['vlan_name'], $vlan['vlan_type'], $vlan['vlan_status'], ''];
        }
    }
}

$valid['vlans']                             = $discovery_vlans;
$GLOBALS['module_stats'][$module]['status'] = safe_count($valid[$module]);
//if (OBS_DEBUG && $GLOBALS['module_stats'][$module]['status']) { print_vars($valid[$module]); }

/* Process discovered ports vlans */
$table_rows  = [];
$vlan_params = ['vlan', 'baseport', 'priority', 'state', 'cost']; // FIXME. move STP to separate table
foreach ($discovery_ports_vlans as $ifIndex => $vlans) {
    foreach ($vlans as $vlan_id => $vlan) {
        $port = get_port_by_index_cache($device, $ifIndex);
        if (!is_array($port)) {
            continue;
        } // Port not founded, skip

        $table_rows[] = [$ifIndex, $port['port_label_short'], $vlan['vlan'], $vlan['priority'], $vlan['state'], $vlan['cost']];

        $vlan_update = [];
        if (isset($ports_vlans_db[$port['port_id']][$vlan_id])) {
            // Port vlan already in db, compare
            foreach ($vlan_params as $param) {
                if ($ports_vlans_db[$port['port_id']][$vlan_id] != $vlan[$param]) {
                    $vlan_update[$param] = $vlan[$param];
                }
            }

            $id = $ports_vlans_db[$port['port_id']][$vlan_id]['port_vlan_id'];
            if (count($vlan_update)) {
                dbUpdate($vlan_update, 'ports_vlans', '`port_vlan_id` = ?', [$id]);
                $module_stats[$vlan_id]['P'] = 'U';
                $GLOBALS['module_stats']['ports_vlans']['updated']++;
            } else {
                $module_stats[$vlan_id]['P'] = '.';
                $GLOBALS['module_stats']['ports_vlans']['unchanged']++;
            }
        } else {
            // New port vlan discovered
            $vlan_update              = $vlan;
            $vlan_update['device_id'] = $device['device_id'];
            $vlan_update['port_id']   = $port['port_id'];

            $id                          = dbInsert($vlan_update, 'ports_vlans');
            $module_stats[$vlan_id]['P'] = '+';
            $GLOBALS['module_stats']['ports_vlans']['added']++;
        }

        // Store processed IDs
        $ports_vlans[$port['port_id']][$vlan_id] = $id;

    }
}
$table_headers = ['%WifIndex%n', '%WifDescr%n', '%WVlan%n', '%WSTP: Priority%n', '%WState%n', '%WCost%n'];
print_cli_table($table_rows, $table_headers);
/* End process ports vlans */

// Clean removed per port vlans
foreach ($ports_vlans_db as $port_id => $vlans) {
    foreach ($vlans as $vlan_id => $vlan) {
        if (empty($ports_vlans[$port_id][$vlan_id])) {
            dbDelete('ports_vlans', "`port_vlan_id` = ?", [$ports_vlans_db[$port_id][$vlan_id]['port_vlan_id']]);
            $module_stats[$vlan_id]['P'] = '-';
            $GLOBALS['module_stats']['ports_vlans']['deleted']++;
        }
    }
}

$valid['ports_vlans']                             = $ports_vlans;
$GLOBALS['module_stats']['ports_vlans']['status'] = count($valid['ports_vlans']);
//if (OBS_DEBUG && $GLOBALS['module_stats']['ports_vlans']['status']) { print_vars($valid['ports_vlans']); }


// Print vlan specific module stats (P - ports, V - vlans, S - spannigtree)

if ($module_stats) {
    $msg = "Module [ $module ] stats:";
    foreach ($module_stats as $vlan_id => $stat) {
        $msg .= ' ' . $vlan_id . '[';
        foreach ($stat as $k => $v) {
            $msg .= $k . $v;
        }
        $msg .= ']';
    }
    echo($msg);
}

unset($vlans_db, $ports_vlans_db, $ports_vlans, $discovery_vlans, $discovery_ports_vlans);

echo(PHP_EOL);

// EOF
