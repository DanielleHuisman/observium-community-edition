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

$table_rows = [];

// Build ifIndex > port and port_id > port cache table
$port_ifIndex_table = [];
$port_table         = [];
foreach (dbFetchRows('SELECT `ifIndex`, `port_id`, `ifDescr`, `port_label_short` FROM `ports` WHERE `device_id` = ?', [ $device['device_id'] ]) as $cache_port) {
    $port_ifIndex_table[$cache_port['ifIndex']] = $cache_port;
    $port_table[$cache_port['port_id']]         = $cache_port;
}

// Build dot1dBasePort > port cache table because people in the '80s were dicks
$dot1dBasePort_table = [];

// Delete old FDB entries (before SELECT for reduce memory usage)
delete_old_fdb_entries($device);

// Build table of existing vlan/mac table
$fdbs_db = [];
$fdbs_dup = [];
foreach (dbFetchRows('SELECT * FROM `vlans_fdb` WHERE `device_id` = ?', [ $device['device_id'] ]) as $fdb_db) {
    if (isset($fdbs_db[$fdb_db['vlan_id']][$fdb_db['mac_address']])) {
        // Duplicate entry? not should be happened, unknown reason.
        $fdbs_dup[] = $fdb_db['fdb_id'];
    } else {
        $fdbs_db[$fdb_db['vlan_id']][$fdb_db['mac_address']] = $fdb_db;
    }
}
if (!empty($fdbs_dup)) {
    print_debug("Warning. Found duplicate FDB entries. Deleting..");
    dbDelete('vlans_fdb', generate_query_values($fdbs_dup, 'fdb_id'));
}
unset($fdbs_dup);

$include_dir = "includes/polling/fdb/";
include("includes/include-dir-mib.inc.php");

////////////////////////////////////////////////////////
// Keep code below here (do not move to mib include!) //
////////////////////////////////////////////////////////

/**********************
 * Cisco BRIDGE-MIB   *
 **********************/
if ($device['os_group'] === 'cisco' && $device['os'] !== 'nxos' &&
    empty($device['snmp_context'])) {

    include __DIR__ . '/fdb/cisco-bridge-mib.php';
}

/**************************
 * non-Cisco Q-BRIDGE-MIB *
 **************************/
if (($device['os_group'] !== 'cisco' || $device['os'] === 'nxos')) { // NX-OS support both ways

    include __DIR__ . '/fdb/q-bridge-mib.php';
}

/**************************
 * Cisco NX-OS BRIDGE-MIB *
 **************************/
if (safe_empty($fdbs) && $device['os'] === 'nxos' && empty($device['snmp_context'])) {
    // NX-OS support both ways, second context per vlan way
    include __DIR__ . '/fdb/cisco-bridge-mib.php';
}

/*******************
 * Last BRIDGE-MIB *
 *******************/
include __DIR__ . '/fdb/bridge-mib.php';

$polled = time();

/******************************
 * Process walked FDB entries *
 ******************************/

print_debug_vars($fdbs);

$fdb_count = [
    'ports' => [],  // Per port FDB count
    'vlans' => [],  // Per vlan
    'total' => 0    // Total FDB count
];

// Loop vlans
foreach ($fdbs as $vlan => $mac_list) {
    // count vlan fdb
    $fdb_count['vlans'][$vlan] = count($mac_list);

    // Loop macs
    foreach ($mac_list as $mac => $data) {

        // Skip incorrect mac entries
        if (strlen($mac) !== 12 || $mac === '000000000000') {
            //unset($fdbs[$vlan][$mac]);
            print_debug_vars($data);
            continue;
        }

        $port_id    = $data['port_id'];
        $port       = $port_table[$port_id];
        $port_index = $data['port_index'];
        $fdb_status = $data['fdb_status'];

        $table_row    = [];
        $table_row[]  = $vlan;
        $table_row[]  = $mac;
        $table_row[]  = is_numeric($data['port_id']) ? $port['port_label_short'] : "Port $port_index";
        $table_row[]  = $data['port_id'];
        $table_row[]  = $data['fdb_status'];
        $table_rows[] = $table_row;
        unset($table_row);

        // if entry already exists
        if (!is_array($fdbs_db[$vlan][$mac])) {
            $q_update = [
              'device_id'       => $device['device_id'],
              'vlan_id'         => $vlan,
              'port_id'         => $port_id,
              'mac_address'     => $mac,
              'fdb_status'      => $fdb_status,
              'fdb_port'        => $port_index,
              'fdb_last_change' => $polled
            ];

            if (!is_numeric($port_id)) {
                $q_update['port_id'] = ['NULL'];
            }
            dbInsertRowMulti($q_update, 'vlans_fdb');
            //$fdb_insert[] = $q_update;
            //dbInsert($q_update, 'vlans_fdb');
            //echo('+');
        } else {
            $q_update = [
              'fdb_id'          => $fdbs_db[$vlan][$mac]['fdb_id'],
              //'device_id'   => $device['device_id'],
              //'vlan_id'     => $vlan,
              'port_id'         => $port_id,
              //'mac_address' => $mac,
              'fdb_status'      => $fdb_status,
              'fdb_port'        => $port_index,
              'fdb_last_change' => $polled,
              'deleted'         => 0
            ];
            $changed  = $fdbs_db[$vlan][$mac]['fdb_last_change'] == 0; // FALSE (for update old entries)
            // if port/status are different, build an update array and update the db
            foreach (['port_id', 'fdb_status', 'fdb_port', 'deleted'] as $field) {
                if ($fdbs_db[$vlan][$mac][$field] != $q_update[$field]) {
                    $changed = TRUE;
                    break;
                }
            }

            if ($changed) {
                if (!is_numeric($port_id)) {
                    $q_update['port_id'] = ['NULL'];
                }
                dbUpdateRowMulti($q_update, 'vlans_fdb', 'fdb_id');
            }
            // remove it from the existing list
            unset($fdbs_db[$vlan][$mac]);
        }

        $fdb_count['total']++; // Total FDB count
        if (is_numeric($port_id)) {
            $fdb_count['ports'][$port_id]++; // Per port FDB count
        }
        //echo(PHP_EOL);
    }
}

// Process Multi Insert/Update
dbProcessMulti('vlans_fdb');

// Delete before insert new entries (for do not show possible duplicates)
// Loop the existing list and delete anything remaining
$fdb_delete = [];
foreach ($fdbs_db as $vlan => $fdb_macs) {
    foreach ($fdb_macs as $mac => $data) {
        $table_row   = [];
        $table_row[] = $vlan;
        $table_row[] = $mac;
        $table_row[] = "Port {$data['port_index']}";
        $table_row[] = $data['port_id'];
        //$table_row[] = $fdb_port;
        //$table_row[] = $data['ifIndex'];
        //echo(str_pad($vlan, 8) . ' | ' . str_pad($mac,12) . ' | ' .  str_pad($data['port_id'],25) .' | '. str_pad($data['fdb_status'],16));
        //echo("-\n");
        if (isset($data['fdb_id'])) {
            // Multi delete (for faster loop)
            //print_debug_vars($data);
            if ($data['deleted']) {
                // Do not poke db change when already deleted
                $table_row[] = '%ydeleted ' . format_unixtime($data['fdb_last_change']) . '%n';
            } else {
                $table_row[]  = '%rdeleted%n';
                $fdb_delete[] = $data['fdb_id'];
            }
        } else {
            // CLEANME. After r12500
            $table_row[] = '%rdeleted%n';
            dbDelete('vlans_fdb', '`device_id` = ? AND `vlan_id` = ? AND `mac_address` = ?', [$device['device_id'], $vlan, $mac]);
        }
        $table_rows[] = $table_row;
    }
}

// MultiDelete old entries
if (safe_count($fdb_delete)) {
    print_debug_vars($fdb_delete);
    // do not delete, set deleted flag
    dbUpdate(['fdb_last_change' => $polled, 'deleted' => 1], 'vlans_fdb', generate_query_values($fdb_delete, 'fdb_id'));
    //dbDelete('vlans_fdb', generate_query_values_ng($fdb_delete, 'fdb_id'));
}

/* MultiInsert new fdb entries
if (safe_count($fdb_insert)) {
  print_debug_vars($fdb_insert);
  dbInsertMulti($fdb_insert, 'vlans_fdb');
}
*/

// FDB count for HP ProCurve
if (!$fdb_count['total'] && is_device_mib($device, 'STATISTICS-MIB')) {
    $fdb_count['total'] = snmp_get_oid($device, 'hpSwitchFdbAddressCount.0', 'STATISTICS-MIB');
}

print_debug_vars($fdb_count);

if (is_numeric($fdb_count['total']) && $fdb_count['total'] > 0) {
    rrdtool_update_ng($device, 'fdb_count', ['value' => $fdb_count['total']]);
    $graphs['fdb_count'] = TRUE;
}

$alert_metrics['fdb_count'] = $fdb_count['total']; // Append fdb count to device metrics

if (is_module_enabled($device, 'ports_fdbcount')) {
    foreach ($fdb_count['ports'] as $port_id => $count) {
        if (!isset($port_table[$port_id])) {
            print_debug("No entry in port table for $port_id");
            continue;
        }
        $port = $port_table[$port_id];

        rrdtool_update_ng($device, 'port-fdbcount', [ 'value' => $count ], get_port_rrdindex($port));
    }
    foreach ($fdb_count['vlans'] as $vlan => $count) {
        rrdtool_update_ng($device, 'vlan-fdbcount', [ 'value' => $count ], $vlan);
    }
}

// print_cli_table($table_rows, array('%WVLAN%n', '%WMAC Address%n', '%WPort%n', '%WPort ID%n', '%WFDB Port%n', '%WifIndex%n', '%WStatus%n'));

// Dont' print since the table can get huge and quite slow.
print_cli_table($table_rows, ['%WVLAN%n', '%WMAC Address%n', '%WPort Name%n', '%WPort ID%n', '%WStatus%n']);

// Clean
unset($fdbs_db, $fdb, $fdb_count, $fdb_insert, $fdb_update, $fdb_delete,
  $table_rows, $port_ifIndex_table, $port_table);

// EOF
