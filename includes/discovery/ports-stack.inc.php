<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

// FIXME. Add here discovery CISCO-STACK-MIB::portTable, CISCO-PAGP-MIB::pagpProtocolConfigTable
// FIXME. Need rename db schema port_id_high -> port_ifIndex_high, port_id_low -> port_ifIndex_low

$query = 'SELECT * FROM `ports_stack` WHERE `device_id` = ?';
foreach (dbFetchRows($query, array($device['device_id'])) as $entry) {
  $stack_db_array[$entry['port_id_high']][$entry['port_id_low']] = $entry;
}

$insert_array = [];
if (is_device_mib($device, 'IF-MIB')) {
  $stack_poll_array = snmpwalk_cache_twopart_oid($device, 'ifStackStatus', array(), 'IF-MIB');

  foreach ($stack_poll_array as $port_ifIndex_high => $entry_high) {
    if (is_numeric($port_ifIndex_high)) {
      $port_high = get_port_by_index_cache($device, $port_ifIndex_high);
      if ($device['os'] === 'ciscosb' && $port_high['ifType'] === 'propVirtual') {
        // Skip stacking on Vlan ports (Cisco SB)
        continue;
      }

      foreach ($entry_high as $port_ifIndex_low => $entry_low) {
        if (is_numeric($port_ifIndex_low)) {
          $port_low = get_port_by_index_cache($device, $port_ifIndex_low);
          if ($device['os'] === 'ciscosb' && $port_low['ifType'] === 'propVirtual') {
            // Skip stacking on Vlan ports (Cisco SB)
            continue;
          }
          $ifStackStatus = $entry_low['ifStackStatus'];
          //if ($ifStackStatus !== 'active') { continue; } // Skip inactive entries

          // Store valid for others
          $valid[$module][$port_ifIndex_high][$port_ifIndex_low] = $ifStackStatus;

          if (isset($stack_db_array[$port_ifIndex_high][$port_ifIndex_low])) {
            if ($stack_db_array[$port_ifIndex_high][$port_ifIndex_low]['ifStackStatus'] == $ifStackStatus) {
              //echo('.');
              $GLOBALS['module_stats'][$module]['unchanged']++;
            } else {
              $update_array = array( 'ifStackStatus' => $ifStackStatus );
              dbUpdate($update_array, 'ports_stack', '`port_stack_id` = ?', array( $entry_low['port_stack_id'] ));
              //echo('U');
              $GLOBALS['module_stats'][$module]['updated']++;
            }
            unset($stack_db_array[$port_ifIndex_high][$port_ifIndex_low]);
          } else {
            // $update_array = array('device_id'     => $device['device_id'],
            //                       'port_id_high'  => $port_ifIndex_high,
            //                       'port_id_low'   => $port_ifIndex_low,
            //                       'ifStackStatus' => $ifStackStatus);
            //dbInsert($update_array, 'ports_stack');
            $insert_array[] = [ 'device_id'     => $device['device_id'],
                                'port_id_high'  => $port_ifIndex_high,
                                'port_id_low'   => $port_ifIndex_low,
                                'ifStackStatus' => $ifStackStatus ];
            $GLOBALS['module_stats'][$module]['added']++;
            //echo('+');
          }
        }
      }
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
        !is_numeric($stack_poll_ports[$port_index_high]['hwTrunkIfIndex'])) { continue; }
    $port_ifIndex_high = $stack_poll_ports[$port_index_high]['hwTrunkIfIndex'];

    $port_high = get_port_by_index_cache($device, $port_ifIndex_high);

    foreach ($entry_high as $port_ifIndex_low => $entry_low) {
      if (!is_numeric($port_ifIndex_low) || $entry_low['hwTrunkValidEntry'] === 'invalid') { continue; }
      if (isset($valid[$module][$port_ifIndex_high][$port_ifIndex_low])) {
        print_debug("Skipped already exist Port Stack $port_ifIndex_high.$port_ifIndex_low:");
        print_debug_vars($entry_low);
        continue;
      }

      $port_low = get_port_by_index_cache($device, $port_ifIndex_low);

      //$ifStackStatus = $entry_low['hwTrunkOperstatus']; // up/down
      $ifStackStatus = $entry_low['hwTrunkRowStatus'];
      //if ($ifStackStatus !== 'active') { continue; } // Skip inactive entries

      // Store valid for others
      $valid[$module][$port_ifIndex_high][$port_ifIndex_low] = $ifStackStatus;

      if (isset($stack_db_array[$port_ifIndex_high][$port_ifIndex_low])) {
        if ($stack_db_array[$port_ifIndex_high][$port_ifIndex_low]['ifStackStatus'] == $ifStackStatus) {
          $GLOBALS['module_stats'][$module]['unchanged']++;
        } else {
          $update_array = [ 'ifStackStatus' => $ifStackStatus ];
          dbUpdate($update_array, 'ports_stack', '`port_stack_id` = ?', [ $entry_low['port_stack_id'] ]);
          $GLOBALS['module_stats'][$module]['updated']++;
        }
        unset($stack_db_array[$port_ifIndex_high][$port_ifIndex_low]);
      } else {
        $insert_array[] = [ 'device_id'     => $device['device_id'],
                            'port_id_high'  => $port_ifIndex_high,
                            'port_id_low'   => $port_ifIndex_low,
                            'ifStackStatus' => $ifStackStatus ];
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
foreach ($stack_db_array as $port_ifIndex_high => $array) {
  foreach ($array as $port_ifIndex_low => $entry) {
    print_debug('DELETE STACK: '.$device['device_id'].' '.$port_ifIndex_low.' '.$port_ifIndex_high);
    //dbDelete('ports_stack', '`device_id` =  ? AND port_id_high = ? AND port_id_low = ?', array($device['device_id'], $port_ifIndex_high, $port_ifIndex_low));
    //echo('-');
    $delete_array[] = $entry['port_stack_id'];
    $GLOBALS['module_stats'][$module]['deleted']++;
  }
}
// MultiDelete old entries
if (count($delete_array)) {
  print_debug_vars($delete_array);
  dbDelete('ports_stack', generate_query_values($delete_array, 'port_stack_id', NULL, FALSE));
}

unset($insert_array, $update_array, $delete_array);

echo(PHP_EOL);

// EOF
