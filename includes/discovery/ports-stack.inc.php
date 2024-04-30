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

// FIXME. Add here discovery CISCO-STACK-MIB::portTable, CISCO-PAGP-MIB::pagpProtocolConfigTable
// FIXME. Need rename db schema port_id_high -> port_ifIndex_high, port_id_low -> port_ifIndex_low

$stack_db_dup   = [];
$stack_db_array = [];
foreach (dbFetchRows('SELECT * FROM `ports_stack` WHERE `device_id` = ?', [$device['device_id']]) as $entry) {
    if (isset($stack_db_array[$entry['port_id_high']][$entry['port_id_low']])) {
        $stack_db_dup[] = $entry['port_stack_id'];
        continue;
    }
    $stack_db_array[$entry['port_id_high']][$entry['port_id_low']] = $entry;
}
if (!safe_empty($stack_db_dup)) {
    print_debug('Found '.count($stack_db_dup).' duplicate ports stack entries, cleaning..');
    dbDelete('ports_stack', generate_query_values($stack_db_dup, 'port_stack_id'));
}

// Generate ifIndex -> port_id cache
$idx_id = [];
foreach (dbFetchRows("SELECT `ifIndex`, `port_id` FROM ports WHERE `device_id` = ?", [$device['device_id']]) as $port) {
    $idx_id[$port['ifIndex']] = $port['port_id'];
}

$ifmib_stack  = FALSE;
$insert_array = [];
if (is_device_mib($device, 'IF-MIB')) {
    $stack_poll_array = snmpwalk_cache_twopart_oid($device, 'ifStackStatus', [], 'IF-MIB');

    foreach ($stack_poll_array as $port_ifIndex_high => $entry_high) {
        if (is_numeric($port_ifIndex_high)) {
            if ($device['os'] === 'ciscosb') {
                $port_high = get_port_by_index_cache($device, $port_ifIndex_high); // Limit port queries to specific os(es)
                if ($port_high['ifType'] === 'propVirtual') {
                    // Skip stacking on Vlan ports (Cisco SB)
                    continue;
                }
            }

            foreach ($entry_high as $port_ifIndex_low => $entry_low) {
                if (is_numeric($port_ifIndex_low)) {
                    if ($device['os'] === 'ciscosb') {
                        $port_low = get_port_by_index_cache($device, $port_ifIndex_low); // Limit port queries to specific os(es)
                        if ($port_low['ifType'] === 'propVirtual') {
                            // Skip stacking on Vlan ports (Cisco SB)
                            continue;
                        }
                    }

                    if ($port_ifIndex_low == 0 || $port_ifIndex_high == 0 || !isset($idx_id[$port_ifIndex_low]) || !isset($idx_id[$port_ifIndex_high])) {
                        continue;
                    }

                    $ifStackStatus = $entry_low['ifStackStatus'];
                    //if ($ifStackStatus === 'notInService') { continue; } // FIXME. Skip inactive entries
                    if ($ifStackStatus === 'notInService' && ($port_ifIndex_low == 0 || $port_ifIndex_high == 0)) {
                        print_debug("Skipped inactive stack entry: high [$port_ifIndex_high] & low [$port_ifIndex_low], ifStackStatus [$ifStackStatus].");
                        continue;
                    }

                    // Set stacks found by IF-MIB
                    $ifmib_stack = TRUE;

                    // Get port_id for upper and lower layer ports
                    $port_id_high = $idx_id[$port_ifIndex_high];
                    $port_id_low  = $idx_id[$port_ifIndex_low];

                    // Store valid for others
                    $valid[$module][$port_id_high][$port_id_low] = $ifStackStatus;

                    if (isset($stack_db_array[$port_id_high][$port_id_low])) {
                        if ($stack_db_array[$port_id_high][$port_id_low]['ifStackStatus'] == $ifStackStatus) {
                            //echo('.');
                            $GLOBALS['module_stats'][$module]['unchanged']++;
                        } else {
                            $update_array = ['ifStackStatus' => $ifStackStatus];
                            dbUpdate($update_array, 'ports_stack', '`port_stack_id` = ?', [$entry_low['port_stack_id']]);
                            //echo('U');
                            $GLOBALS['module_stats'][$module]['updated']++;
                        }
                        unset($stack_db_array[$port_id_high][$port_id_low]);
                    } else {
                        // $update_array = array('device_id'     => $device['device_id'],
                        //                       'port_id_high'  => $port_ifIndex_high,
                        //                       'port_id_low'   => $port_ifIndex_low,
                        //                       'ifStackStatus' => $ifStackStatus);
                        //dbInsert($update_array, 'ports_stack');
                        $insert_array[] = [
                          'device_id'     => $device['device_id'],
                          'port_id_high'  => $port_id_high,
                          'port_id_low'   => $port_id_low,
                          'ifStackStatus' => $ifStackStatus
                        ];
                        $GLOBALS['module_stats'][$module]['added']++;
                        //echo('+');
                    }
                }
            }
        }
    }
}

if (!$ifmib_stack && is_device_mib($device, 'IEEE8023-LAG-MIB') &&
    $stack_list_ports = snmpwalk_cache_oid($device, 'dot3adAggPortListPorts', [], 'IEEE8023-LAG-MIB')) {
    // ifDescr.51 = 1/1/51
    // ifDescr.52 = 1/1/52
    // ifDescr.769 = lag1
    // IEEE8023-LAG-MIB::dot3adAggPortListPorts.769 = STRING: "1/1/51,1/1/52"
    // IEEE8023-LAG-MIB::dot3adAggPortSelectedAggID.51 = INTEGER: 769
    // IEEE8023-LAG-MIB::dot3adAggPortSelectedAggID.52 = INTEGER: 769
    // IEEE8023-LAG-MIB::dot3adAggPortAttachedAggID.51 = INTEGER: 769
    // IEEE8023-LAG-MIB::dot3adAggPortAttachedAggID.52 = INTEGER: 769
    $stack_agg_ports = snmpwalk_cache_oid($device, 'dot3adAggPortSelectedAggID', [], 'IEEE8023-LAG-MIB');
    $stack_agg_ports = snmpwalk_cache_oid($device, 'dot3adAggPortAttachedAggID', $stack_agg_ports, 'IEEE8023-LAG-MIB');
    $stack_agg_ports = snmpwalk_cache_oid($device, 'dot3adAggPortAggregateOrIndividual', $stack_agg_ports, 'IEEE8023-LAG-MIB');

    // IEEE8023-LAG-MIB::dot3adAggPortActorAdminState.1000009 = BITS: E0 lacpActivity(0) lacpTimeout(1) aggregation(2)
    // IEEE8023-LAG-MIB::dot3adAggPortActorAdminState.1000010 = BITS: E0 lacpActivity(0) lacpTimeout(1) aggregation(2)
    $stack_agg_ports = snmpwalk_cache_oid($device, 'dot3adAggPortActorAdminState', $stack_agg_ports, 'IEEE8023-LAG-MIB', NULL, OBS_SNMP_ALL_HEX);
    print_debug_vars($stack_agg_ports);

    foreach ($stack_agg_ports as $port_ifIndex_low => $entry_low) {
        $entry_low['admin_bits'] = get_bits_state_array($entry_low['dot3adAggPortActorAdminState'], 'IEEE8023-LAG-MIB', 'LacpState');
        print_debug_vars($entry_low);

        $port_ifIndex_high = $entry_low['dot3adAggPortAttachedAggID'];
        $ifStackStatus     = 'active'; // always active

        if ($entry_low['dot3adAggPortAggregateOrIndividual'] === 'false' || !in_array('aggregation', (array)$entry_low['admin_bits'], TRUE)) {
            // Skip not aggregate ports
            print_debug("Skipped inactive stack entry: high [$port_ifIndex_high] & low [$port_ifIndex_low], dot3adAggPortAggregateOrIndividual [{$entry_low['dot3adAggPortAggregateOrIndividual']}].");
            continue;
        }

        if ($port_ifIndex_low == 0 || $port_ifIndex_high == 0 || !isset($idx_id[$port_ifIndex_low]) || !isset($idx_id[$port_ifIndex_high])) {
            continue;
        }

        // Get port_id for upper and lower layer ports
        $port_id_high = $idx_id[$port_ifIndex_high];
        $port_id_low  = $idx_id[$port_ifIndex_low];

        // Store valid for others
        $valid[$module][$port_id_high][$port_id_low] = $ifStackStatus;

        if (isset($stack_db_array[$port_id_high][$port_id_low])) {
            if ($stack_db_array[$port_id_high][$port_id_low]['ifStackStatus'] == $ifStackStatus) {
                //echo('.');
                $GLOBALS['module_stats'][$module]['unchanged']++;
            } else {
                $update_array = ['ifStackStatus' => $ifStackStatus];
                dbUpdate($update_array, 'ports_stack', '`port_stack_id` = ?', [$entry_low['port_stack_id']]);
                //echo('U');
                $GLOBALS['module_stats'][$module]['updated']++;
            }
            unset($stack_db_array[$port_id_high][$port_id_low]);
        } else {
            // $update_array = array('device_id'     => $device['device_id'],
            //                       'port_id_high'  => $port_ifIndex_high,
            //                       'port_id_low'   => $port_ifIndex_low,
            //                       'ifStackStatus' => $ifStackStatus);
            //dbInsert($update_array, 'ports_stack');
            $insert_array[] = [
              'device_id'     => $device['device_id'],
              'port_id_high'  => $port_id_high,
              'port_id_low'   => $port_id_low,
              'ifStackStatus' => $ifStackStatus
            ];
            $GLOBALS['module_stats'][$module]['added']++;
            //echo('+');
        }
    }
}

/*
if (OBS_DEBUG && is_device_mib($device, 'HUAWEI-IF-EXT-MIB')) {
  $tmp = snmpwalk_cache_twopart_oid($device, 'hwTrunkMemEntry', [], 'HUAWEI-IF-EXT-MIB');
  print_vars($tmp);
  unset($tmp);
}
*/
if (is_device_mib($device, 'HUAWEI-IF-EXT-MIB')) {
    // hwTrunkValidEntry.45.146 = valid
    // hwTrunkSelectStatus.45.146 = trunkSelected
    // hwTrunkLacpStatus.45.146 = enabled
    // hwTrunkOperstatus.45.146 = up
    // hwTrunkPortWeight.45.146 = 1
    // hwTrunkRowStatus.45.146 = active
    // hwTrunkPortPriority.45.146 = 32768
    // hwTrunkPortStatReset.45.146 = ready
    $stack_poll_array = snmpwalk_cache_twopart_oid($device, 'hwTrunkRowStatus', [], 'HUAWEI-IF-EXT-MIB');
    if (snmp_status()) {
        $stack_poll_array = snmpwalk_cache_twopart_oid($device, 'hwTrunkValidEntry', $stack_poll_array, 'HUAWEI-IF-EXT-MIB');
        $stack_poll_ports = snmpwalk_cache_oid($device, 'hwTrunkIfIndex', [], 'HUAWEI-IF-EXT-MIB');
    }

    foreach ($stack_poll_array as $port_index_high => $entry_high) {

        if (!isset($stack_poll_ports[$port_index_high]['hwTrunkIfIndex']) ||
            !is_numeric($stack_poll_ports[$port_index_high]['hwTrunkIfIndex'])) {
            continue;
        }
        $port_ifIndex_high = $stack_poll_ports[$port_index_high]['hwTrunkIfIndex'];

        $port_high = get_port_by_index_cache($device, $port_ifIndex_high);

        foreach ($entry_high as $port_ifIndex_low => $entry_low) {
            if (!is_numeric($port_ifIndex_low) || $entry_low['hwTrunkValidEntry'] === 'invalid') {
                continue;
            }
            if (!isset($idx_id[$port_ifIndex_low], $idx_id[$port_ifIndex_high]) || $port_ifIndex_low == 0 || $port_ifIndex_high == 0) {
                continue;
            }

            $port_low = get_port_by_index_cache($device, $port_ifIndex_low);

            //$ifStackStatus = $entry_low['hwTrunkOperstatus']; // up/down
            $ifStackStatus = $entry_low['hwTrunkRowStatus'];
            //if ($ifStackStatus !== 'active') { continue; } // Skip inactive entries

            // Get port_id for upper and lower layer ports
            $port_id_high = $idx_id[$port_ifIndex_high];
            $port_id_low  = $idx_id[$port_ifIndex_low];

            if (isset($valid[$module][$port_id_high][$port_id_low])) {
                print_debug("Skipped already exist Port Stack $port_ifIndex_high.$port_ifIndex_low:");
                print_debug_vars($entry_low);
                continue;
            }

            // Store valid for others
            $valid[$module][$port_id_high][$port_id_low] = $ifStackStatus;

            if (isset($stack_db_array[$port_id_high][$port_id_low])) {
                if ($stack_db_array[$port_id_high][$port_id_low]['ifStackStatus'] == $ifStackStatus) {
                    $GLOBALS['module_stats'][$module]['unchanged']++;
                } else {
                    $update_array = ['ifStackStatus' => $ifStackStatus];
                    dbUpdate($update_array, 'ports_stack', '`port_stack_id` = ?', [$entry_low['port_stack_id']]);
                    $GLOBALS['module_stats'][$module]['updated']++;
                }
                unset($stack_db_array[$port_id_high][$port_id_low]);
            } else {
                $insert_array[] = [
                  'device_id'     => $device['device_id'],
                  'port_id_high'  => $port_id_high,
                  'port_id_low'   => $port_id_low,
                  'ifStackStatus' => $ifStackStatus
                ];
                $GLOBALS['module_stats'][$module]['added']++;
            }
        }
    }
}

// MultiInsert
if (count($insert_array)) {
    print_debug_vars($insert_array);
    dbInsertMulti($insert_array, 'ports_stack');
}

$delete_array = [];
foreach ($stack_db_array as $port_id_high => $array) {
    foreach ($array as $port_id_low => $entry) {
        print_debug('DELETE STACK: ' . $device['device_id'] . ' ' . $port_id_low . ' ' . $port_id_high);
        //dbDelete('ports_stack', '`device_id` = ? AND port_id_high = ? AND port_id_low = ?', array($device['device_id'], $port_ifIndex_high, $port_ifIndex_low));
        //echo('-');
        $delete_array[] = $entry['port_stack_id'];
        $GLOBALS['module_stats'][$module]['deleted']++;
    }
}
// MultiDelete old entries
if (count($delete_array)) {
    print_debug_vars($delete_array);
    dbDelete('ports_stack', generate_query_values($delete_array, 'port_stack_id'));
}

unset($insert_array, $update_array, $delete_array);

echo(PHP_EOL);
