<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     housekeeping
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Store highest device ID so we don't delete data from devices that were added during our run.
// Counting on mysql auto_increment to never give out a lower ID, obviously.
//$max_id = -1;
$max_id = dbShowNextID('devices');

// Fetch all existing device IDs and update the maximum ID.
$devices = [];
foreach (dbFetchRows("SELECT `device_id` FROM `devices` ORDER BY `device_id` ASC") as $device) {
    $devices[] = $device['device_id'];

    // Not sure in which cases this can happen, but I keep this for compatibility
    if ($device['device_id'] > $max_id) {
        $max_id = $device['device_id'];
    }
}


// Cleanup tables with links to devices that on longer exist
foreach ($config['device_tables'] as $table) {
    $where = '`device_id` NOT IN (' . implode(',', $devices) . ') AND `device_id` < ?';
    //$where = '`device_id` NOT IN (' . implode(',', $devices) . ') OR `device_id` > ?';
    if ($table === 'observium_processes') {
        $where .= ' AND `device_id` != 0';
    } elseif ($table === 'eventlog') {
        // Global events with device_id = 0
        $where .= " AND `entity_type` NOT IN ('global', 'info')";
    }

    $rows  = dbFetchRows("SELECT 1 FROM `$table` WHERE $where", [$max_id], $test);
    $count = safe_count($rows);
    if ($count) {
        if ($prompt) {
            $answer = print_prompt("$count rows in table '$table' for non-existing devices will be deleted");
        }
        if ($answer) {
            // IP addresses need additionally check if associated network is empty
            if ($table === 'ipv4_addresses') {
                $network_table = 'ipv4_networks';
                $network_where = '`ipv4_network_id` = ?';
                $network_ids   = dbFetchColumn("SELECT DISTINCT `ipv4_network_id` FROM `$table` WHERE $where", [$max_id]);
                print_debug_vars($network_ids);
            } elseif ($table === 'ipv6_addresses') {
                $network_table = 'ipv6_networks';
                $network_where = '`ipv6_network_id` = ?';
                $network_ids   = dbFetchColumn("SELECT DISTINCT `ipv6_network_id` FROM `$table` WHERE $where", [$max_id]);
                print_debug_vars($network_ids);
            }

            // Remove stale entries
            print_debug("Database cleanup for table '$table': deleted $count entries");
            if (!$test) {
                $table_status = dbDelete($table, $where, [$max_id]);
                logfile("housekeeping.log", "Database cleanup for table '$table': deleted $count entries");
            }

            // Clean network ids after delete ip addresses if network unused
            if ($table_status &&
                ($table === 'ipv4_addresses' || $table === 'ipv6_addresses')) {
                foreach ($network_ids as $network_id) {
                    if (!dbExist($table, $network_where, [$network_id], $test) && !$test) {
                        $network_status = dbDelete($network_table, $network_where, [$network_id]);
                    }
                }
            }
        }
    } elseif ($prompt) {
        print_message("No orphaned rows found in table '$table'.");
    }
}

// Cleanup autodiscovery entries
if (dbExist('autodiscovery', '`remote_device_id` IS NOT NULL AND `remote_device_id` NOT IN (SELECT `device_id` FROM `devices`)', NULL, $test)) {
    if (!$test) {
        $table_status = dbDelete('autodiscovery', '`remote_device_id` IS NOT NULL AND `remote_device_id` NOT IN (SELECT `device_id` FROM `devices`)');
        logfile("housekeeping.log", "Deleted stale autodiscovery entries.");
    }
    print_debug("Deleted stale autodiscovery entries.");
}

// Cleanup common entity tables with links to devices that no longer exist
// Loop for found stale entity entries
/*
 * Probably not the best idea to remove user-generated configuration like this, this very occasionally seems to delete billing ports
 *
$where = " WHERE " . generate_query_values_ng([ 'bill', 'group' ], 'entity_type', '!='); // Exclude pseudo-entities from permissions checks
foreach ($config['entity_tables'] as $table) {
  $query = "SELECT DISTINCT `entity_type` FROM `$table` $where";
  foreach (dbFetchColumn($query, NULL, $test) as $entity_type) {
    $translate = entity_type_translate_array($entity_type);
    if (safe_empty($translate['device_id_field'])) { continue; }
    $id_field = $translate['id_field'];
    $query = 'SELECT `entity_id` FROM `'.$table.'` WHERE `entity_type` = ? AND `entity_id` NOT IN (SELECT `' . $id_field . '` FROM `' . $translate['table'] . '`)';
    $ids = dbFetchColumn($query, [ $entity_type ], $test);
    $count = safe_count($ids);
    if ($count) {
      if ($prompt) {
        $answer = print_prompt("$count rows in table '$table' for non-existing '$entity_type' entities will be deleted");
      }

      // Remove stale entries
      print_debug("Database cleanup for table '$table': deleted $count '$entity_type' entities");
      if (!$test) {
        $table_status = dbDelete($table, "`entity_type` = ? ".generate_query_values_and($ids, 'entity_id'), [ $entity_type ]);
        logfile("housekeeping.log", "Database cleanup for table '$table': deleted $count '$entity_type' entities");
      }
    } elseif ($prompt) {
      print_message("No orphaned rows found in table '$table' for '$entity_type' entities.");
    }
  }
}
*/

// Cleanup duplicate entries in the device_graphs table
$graphs = [];
foreach (dbFetchRows("SELECT * FROM `device_graphs`", NULL, $test) as $entry) {
    $graphs[$entry['device_id']][$entry['graph']][] = $entry['device_graph_id'];
}
print_debug_vars($graphs);

foreach ($graphs as $device_id => $device_graph) {
    foreach ($device_graph as $graph => $data) {
        if (safe_count($data) > 1) {
            // More than one entry for a single graph type for this device, let's clean up.
            // Leave the first entry intact, chop it off the array
            $device_graph_ids   = array_slice($data, 1);
            $device_graph_count = safe_count($device_graph_ids);
            if ($prompt) {
                $answer = print_prompt($device_graph_count . " duplicate graph rows of type $graph for device $device_id will be deleted");
            }
            if ($answer) {
                if (!$test) {
                    $table_status = dbDelete('device_graphs', "`device_graph_id` IN (?)", [$device_graph_ids]);
                    logfile("housekeeping.log", "Deleted " . $device_graph_count . " duplicate graph rows of type $graph for device $device_id");
                }
                print_debug("Deleted " . $device_graph_count . " duplicate graph rows of type $graph for device $device_id");
            }
        }
    }
}

// EOF
