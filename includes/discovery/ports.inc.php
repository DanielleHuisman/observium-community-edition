<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Build SNMP Cache Array

$port_stats = [];
$port_oids  = ["ifDescr", "ifAlias", "ifName", "ifType", "ifOperStatus"];

print_cli_data_field("Caching OIDs", 3);
if (is_device_mib($device, "IF-MIB")) {
    foreach ($port_oids as $oid) {
        print_cli($oid . " ");
        $port_stats = snmpwalk_cache_oid($device, $oid, $port_stats, "IF-MIB");
    }
} else { // End IF-MIB permitted
    // This part for devices who not have IF-MIB stats, but have own vendor tree with ports
}
print_cli(PHP_EOL); // END CACHING OIDS


foreach (get_device_mibs_permitted($device) as $mib) {
    merge_private_mib($device, 'ports', $mib, $port_stats, $port_oids);
}

// Additionally include per MIB functions and snmpwalks (uses include_once)
$include_lib = TRUE;
$include_dir = "includes/discovery/ports/";
include("includes/include-dir-mib.inc.php");

// End Building SNMP Cache Array

print_debug_vars($port_stats, 1);

// Build array of ports in the database

// FIXME -- this stuff is a little messy, looping the array to make an array just seems wrong. :>
//       -- i can make it a function, so that you don't know what it's doing.
//       -- $ports_db = adamasMagicFunction($ports_db); ?

print_cli_data_field("Caching DB", 3);

$ports_db             = [];
$ports_ids            = [];
$ports_duplicates_ids = [];
foreach (dbFetchRows("SELECT * FROM `ports` WHERE `device_id` = ?", [$device['device_id']]) as $port) {
    // Note, deleted / disabled and up ports can have same indexes!
    $key = $port['ifIndex']; // Possible to use other key in future

    // Mark for clean deleted/disabled duplicates
    if (isset($ports_db[$key])) {
        $old_port = $ports_db[$key];
        if ($old_port['deleted'] || $old_port['disabled']) {
            // Rewrite old cached entry
            $ports_duplicates_ids[$old_port['port_id']] = $old_port['port_id'];
            unset($ports_ids[$old_port['port_id']]);
        } else {
            // Just skip duplicate entry
            $ports_duplicates_ids[$port['port_id']] = $port['port_id'];
            continue;
        }
    }

    $ports_db[$key]              = $port;
    $ports_ids[$port['port_id']] = $port;
}

print_cli(count($ports_db) . " ports" . PHP_EOL);

print_cli_data_field("Discovering ports", 3);

$table_rows   = [];
$ports_insert = [];

// New interface detection
foreach ($port_stats as $ifIndex => $port) {
    $port['ifIndex'] = $ifIndex;
    $key             = $ifIndex; // Possible to use other key in future

    // Fix ord (UTF-8) chars, ie:
    // ifAlias.3 = Conexi<F3>n de <E1>rea local* 3
    foreach (['ifAlias', 'ifDescr', 'ifName'] as $oid_fix) {
        if (isset($port[$oid_fix])) {
            $port[$oid_fix] = snmp_fix_string($port[$oid_fix]);
        }
    }

    // On some Brocade NOS
    if ($port['ifOperStatus'] === '-1') {
        $port['ifOperStatus'] = 'unknown';
    }

    $table_row = [ $ifIndex, truncate($port['ifDescr'], 30), $port['ifName'], truncate($port['ifAlias'], 20), $port['ifType'], $port['ifOperStatus'] ];
    // Check the port against our filters.
    if (is_port_valid($device, $port)) {
        // Not ignored ports

        $table_row[] = '%gno%n';

        if (!isset($ports_db[$key])) {
            // New port
            process_port_label($port, $device); // Process ifDescr if needed
            $table_row[1] = truncate($port['ifDescr'], 30);

            $ports_insert[] = [ 'device_id' => $device['device_id'],
                                'ifIndex'   => $ifIndex,
                                'ifAlias'   => $port['ifAlias'],
                                'ifDescr'   => $port['ifDescr'],
                                'ifName'    => $port['ifName'],
                                'ifType'    => $port['ifType'],
                                'ignore'    => $port['ignore'] ?? '0',
                                'disabled'  => $port['disabled'] ?? '0',
            ];
            //$port_id = dbInsert(array('device_id' => $device['device_id'], 'ifIndex' => $ifIndex, 'ifAlias' => $port['ifAlias'], 'ifDescr' => $port['ifDescr'], 'ifName' => $port['ifName'], 'ifType' => $port['ifType']), 'ports');
            //$ports_db[$ifIndex] = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ?", array($device['device_id'], $ifIndex));
            echo(" " . $port['port_label'] . "(" . $ifIndex . ")");
        } elseif ($ports_db[$key]['deleted'] == "1") {
            // Undeleted port
            dbUpdate(['deleted' => '0'], 'ports', '`port_id` = ?', [$ports_db[$key]['port_id']]);
            log_event("Interface DELETED mark removed", $device, 'port', $ports_db[$key]);
            $ports_db[$key]['deleted'] = "0";
            echo("U");

            // We've seen it. Remove it from the cache.
            $port_id = $ports_db[$key]['port_id'];
            unset($ports_ids[$port_id]);
        } else {
            echo(".");

            // Update RRD DS max for ports 40G+
            if (isset($ports_db[$key]['ifSpeed']) && $ports_db[$key]['ifSpeed'] > 40000000000) {
                //$rrd_options['speed'] += 20000000000; // DEBUG
                //$rrd_options['update_max'] = TRUE;
                $filename   = rrdtool_generate_filename($config['rrd_types']['port'], $key);
                $fsfilename = get_rrd_path($device, $filename);
                $rrd_info   = rrdtool_file_info($fsfilename);
                //print_vars($rrd_info);
                if ($rrd_info && is_numeric($rrd_info['DS']['INOCTETS']['max'])) {
                    $max = (float)$rrd_info['DS']['INOCTETS']['max'];
                    //print_vars($max);
                    //print_vars($ports_db[$key]['ifSpeed'] / 8);
                    if ($max < ($ports_db[$key]['ifSpeed'] / 8)) {
                        $rrd_options = ['speed' => $ports_db[$key]['ifSpeed'], 'update_max' => TRUE];
                        rrdtool_update_ds($device, 'port', $key, $rrd_options);
                        log_event("Interface RRD updated MAX DSes", $device, 'port', $ports_db[$key], 7);
                    }
                }
            }
            // We've seen it. Remove it from the cache.
            $port_id = $ports_db[$key]['port_id'];
            unset($ports_ids[$port_id]);
        }

    } else {
        // Port incorrect/ignored

        $table_row[] = '%ryes%n';
        if (isset($ports_db[$key])) {
            if ($ports_db[$key]['deleted'] != "1") {
                dbUpdate(['deleted' => '1', 'ifLastChange' => date('Y-m-d H:i:s', time())], 'ports', '`port_id` = ?', [$ports_db[$key]['port_id']]);
                log_event("Interface was marked as DELETED", $device, 'port', $ports_db[$key]);
                $ports_db[$key]['deleted'] = "1";
                echo("-");
            }

            // We've seen it. Remove it from the cache.
            $port_id = $ports_db[$key]['port_id'];
            unset($ports_ids[$port_id]);
        }
        echo("X");
    }
    $table_rows[] = $table_row;
}

if (count($ports_insert)) {
    dbInsertMulti($ports_insert, 'ports');
}
// End New interface detection

// Interfaces Clean
// If it's in our $ports_ids list, that means it's not been seen. Mark it deleted.
foreach ($ports_ids as $port_id => $port) {
    if ($port['deleted'] == "0") {
        dbUpdate(['deleted' => '1', 'ifLastChange' => date('Y-m-d H:i:s', time())], 'ports', '`port_id` = ?', [$port_id]);
        log_event("Interface was marked as DELETED", $device, 'port', $port_id);
        echo("-");
    }
}

// Complete remove duplicates
if (count($ports_duplicates_ids)) {
    dbDelete('ports', generate_query_values($ports_duplicates_ids, 'port_id'));
}
// End interfaces clean
echo(PHP_EOL);

$table_headers = ['%WifIndex%n', '%WifDescr%n', '%WifName%n', '%WifAlias%n', '%WifType%n', '%WOper Status%n', '%WIgnored%n'];
print_cli_table($table_rows, $table_headers);

// Clear Variables Here
unset($port_stats, $ports_db, $ports_ids, $ports_duplicates_ids, $ports_insert, $port, $table_rows, $table_headers);

// EOF
