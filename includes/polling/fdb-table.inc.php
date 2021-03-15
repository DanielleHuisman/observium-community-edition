<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$table_rows = array();

// Build ifIndex > port and port_id > port cache table
$port_ifIndex_table = array();
$port_table = array();
foreach (dbFetchRows('SELECT `ifIndex`,`port_id`,`ifDescr`,`port_label_short` FROM `ports` WHERE `device_id` = ?', array($device['device_id'])) as $cache_port)
{
  $port_ifIndex_table[$cache_port['ifIndex']] = $cache_port;
  $port_table[$cache_port['port_id']] = $cache_port;
}

// Build dot1dBasePort > port cache table because people in the '80s were dicks
$dot1dBasePort_table = array();

// Build table of existing vlan/mac table
$fdbs_db = array();
$fdbs_q = dbFetchRows('SELECT * FROM `vlans_fdb` WHERE `device_id` = ?', array($device['device_id']));
foreach ($fdbs_q as $fdb_db)
{
  $fdbs_db[$fdb_db['vlan_id']][$fdb_db['mac_address']] = $fdb_db;
}

$include_dir = "includes/polling/fdb/";
include("includes/include-dir-mib.inc.php");

////////////////////////////////////////////////////////
// Keep code below here (do not move to mib include!) //
////////////////////////////////////////////////////////

/**********************
 * Cisco Q-BRIDGE-MIB *
 **********************/

if (!count($fdbs) && $device['os_group'] == 'cisco' && empty($device['snmp_context']) &&
    is_device_mib($device, 'Q-BRIDGE-MIB')) // Can block by mib permissions!
{

  // I think this is global, not per-VLAN. (in normal world..)
  // But NOPE, this is Cisco way (probably for pvst) @mike
  // See: https://jira.observium.org/browse/OBS-2813
  //
  // From same device example default and vlan 103:
  // snmpbulkwalk -v2c community -m BRIDGE-MIB -M /srv/observium/mibs/rfc:/srv/observium/mibs/net-snmp sw-1917 dot1dBasePortIfIndex
  //BRIDGE-MIB::dot1dBasePortIfIndex.49 = INTEGER: 10101
  //BRIDGE-MIB::dot1dBasePortIfIndex.50 = INTEGER: 10102
  // snmpbulkwalk -v2c community@103 -m BRIDGE-MIB -M /srv/observium/mibs/rfc:/srv/observium/mibs/net-snmp sw-1917 dot1dBasePortIfIndex
  //BRIDGE-MIB::dot1dBasePortIfIndex.1 = INTEGER: 10001
  //BRIDGE-MIB::dot1dBasePortIfIndex.3 = INTEGER: 10003
  //BRIDGE-MIB::dot1dBasePortIfIndex.4 = INTEGER: 10004
  //...
  // But I will try to pre-cache, this fetch port association for default (1) vlan only!
  $dot1dBasePortIfIndex[1] = snmpwalk_cache_oid($device, 'dot1dBasePortIfIndex', array(), 'BRIDGE-MIB');
  foreach ($dot1dBasePortIfIndex[1] as $base_port => $data)
  {
    $dot1dBasePort_table[$base_port] = $port_ifIndex_table[$data['dot1dBasePortIfIndex']];
  }

  // Fetch list of active VLANs (with vlan context exist)
  foreach (dbFetchRows('SELECT DISTINCT `vlan_vlan` FROM `vlans` WHERE `device_id` = ? AND `vlan_context` = ? AND (`vlan_status` = ? OR `vlan_status` = ?)', array($device['device_id'], 1, 'active', 'operational')) as $cisco_vlan)
  {
    $vlan = $cisco_vlan['vlan_vlan'];

    /* Not actual, since we check if context exist in vlans discovery
    list($ios_version) = explode('(', $device['version']);
    // vlan context not worked on Cisco IOS <= 12.1 (SNMPv3)
    if ($device['snmp_version'] == 'v3' && $device['os'] == 'ios' && ($ios_version * 10) <= 121)
    {
      print_error('ERROR: For correct operation please use SNMP v2c for this device.');
      break;
    }

    // Skip hardcoded VLANs used for non-Ethernet purposes.
    if (!is_numeric($vlan) || ($vlan >= 1002 && $vlan <= 1005)) { continue; }
    */

    // Set per-VLAN context
    $device_context = $device;
    // Add vlan context for snmp auth
    if ($device['snmp_version'] == 'v3')
    {
      $device_context['snmp_context'] = 'vlan-' . $vlan;
    } else {
      $device_context['snmp_context'] = $vlan;
    }
    //$device_context['snmp_retries'] = 1;         // Set retries to 0 for speedup walking

    //dot1dTpFdbAddress[0:7:e:6d:55:41] 0:7:e:6d:55:41
    //dot1dTpFdbPort[0:7:e:6d:55:41] 28
    //dot1dTpFdbStatus[0:7:e:6d:55:41] learned
    $dot1dTpFdbEntry_table = snmp_walk_multipart_oid($device_context, 'dot1dTpFdbEntry', array(), 'BRIDGE-MIB', NULL, OBS_SNMP_ALL_TABLE);

    // Detection shit snmpv3 authorization errors for contexts
    if ($GLOBALS['exec_status']['exitcode'] != 0)
    {
      unset($device_context);
      if ($device['snmp_version'] == 'v3')
      {
        print_error("ERROR: For proper usage of 'vlan-' context on cisco device with SNMPv3, it is necessary to add 'match prefix' in snmp-server config.");
      } else {
        print_error('ERROR: Device does not support per-VLAN community.');
      }
      break;
    }
    else if (!snmp_status())
    {
      // Continue if no entries for vlan
      unset($device_context);
      continue;
    }

    foreach ($dot1dTpFdbEntry_table as $mac => $entry)
    {
      $mac      = mac_zeropad($mac);
      $fdb_port = $entry['dot1dTpFdbPort'];

      // If not exist ifIndex associations from previous walks, fetch association for current vlan context
      // This is derp, but I not know better speedup this walks
      if (!isset($dot1dBasePort_table[$fdb_port]) && !isset($dot1dBasePortIfIndex[$vlan]))
      {
        print_debug("Cache dot1dBasePort -> IfIndex association table by vlan $vlan");
        // Need to walk port association for this vlan context
        $dot1dBasePortIfIndex[$vlan] = snmpwalk_cache_oid($device_context, 'dot1dBasePortIfIndex', array(), 'BRIDGE-MIB');
        foreach ($dot1dBasePortIfIndex[$vlan] as $base_port => $data)
        {
          $dot1dBasePort_table[$base_port] = $port_ifIndex_table[$data['dot1dBasePortIfIndex']];
        }
        // Prevent rewalk in cycle if empty output
        if (is_null($dot1dBasePortIfIndex[$vlan]))
        {
          $dot1dBasePortIfIndex[$vlan] = FALSE;
        }
      }

      $data = array();

      $data['port_id']    = $dot1dBasePort_table[$fdb_port]['port_id'];
      $data['port_index'] = $dot1dBasePort_table[$fdb_port]['ifIndex'];
      $data['fdb_status'] = $entry['dot1dTpFdbStatus'];

      $fdbs[$vlan][$mac]= $data;
    }
  }
}
unset($dot1dBasePortIfIndex, $dot1dTpFdbEntry_table);

/**************************
 * non-Cisco Q-BRIDGE-MIB *
 **************************/

if (!count($fdbs) && $device['os_group'] != 'cisco' && is_device_mib($device, 'Q-BRIDGE-MIB')) // Q-BRIDGE-MIB already blacklisted for vrp
{
  //dot1qTpFdbPort[1][0:0:5e:0:1:1] 50
  //dot1qTpFdbStatus[1][0:0:5e:0:1:1] learned

  if ($device['os'] == 'junos')
  {
    // JUNOS doesn't use the actual vlan ids for much in Q-BRIDGE-MIB
    // but we can get the vlan names and use that to lookup the actual
    // vlan ids that were found with JUNIPER-VLAN-MIB during discovery

    // Fetch list of active VLANs
    foreach (dbFetchRows('SELECT `vlan_vlan`,`vlan_name` FROM `vlans` WHERE (`vlan_status` = ? OR `vlan_status` = ?) AND `device_id` = ?', array('active', 'operational', $device['device_id'])) as $entry)
    {
      $vlanidsbyname[$entry['vlan_name']] = $entry['vlan_vlan'];
    }

    // getting the names as listed by Q-BRIDGE-MIB
    // and making a mapping to the real vlan ids
    if (count($vlanidsbyname))
    {
      foreach (snmpwalk_cache_oid($device, 'dot1qVlanStaticName', array(), 'Q-BRIDGE-MIB', NULL, OBS_SNMP_ALL_TABLE) AS $id => $entry)
      {
        $juniper_vlans[$id] = $vlanidsbyname[$entry['dot1qVlanStaticName']];
      }
    }
  }

  // Dell OS10 return strange additional num, see:
  // https://jira.observium.org/browse/OBS-3213
  //dot1qTpFdbPort[4][6:0:24:38:93:c8].0 = 59
  //dot1qTpFdbPort[4][6:0:50:56:95:51].221 = 59
  $dot1qTpFdbEntry_table = snmpwalk_cache_oid($device, 'dot1qTpFdbEntry', array(), 'Q-BRIDGE-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
  if (snmp_status())
  {
    // Build dot1dBasePort
    foreach (snmpwalk_cache_oid($device, 'dot1dBasePortIfIndex', array(), 'BRIDGE-MIB') as $dot1dbaseport => $entry)
    {
      $dot1dBasePort_table[$dot1dbaseport] = $port_ifIndex_table[$entry['dot1dBasePortIfIndex']];
    }

    foreach ($dot1qTpFdbEntry_table as $index => $entry)
    {
      $index_array = explode('.', $index);
      $vlan = array_shift($index_array);
      if (count($index_array) > 6)
      {
        // Remove first (strange, incorrect) mac part
        array_shift($index_array);
      }
      // reimplode index to mac
      $mac = '';
      foreach ($index_array as $mac_num)
      {
        $mac .= dechex($mac_num) . ':';
      }
      $mac = mac_zeropad(trim($mac, ':'));

      // if we have a translated vlan id for Juniper, use it
      if (isset($juniper_vlans[$vlan]))
      {
        $vlan = $juniper_vlans[$vlan];
      }

      $fdb_port = $entry['dot1qTpFdbPort'];

      $data = array();
      $data['port_id']    = $dot1dBasePort_table[$fdb_port]['port_id'];
      $data['port_index'] = $dot1dBasePort_table[$fdb_port]['ifIndex'];
      $data['fdb_status'] = $entry['dot1qTpFdbStatus'];

      $fdbs[$vlan][$mac]  = $data;

    }
  }
}

/*******************
 * Last BRIDGE-MIB *
 *******************/

// Note, BRIDGE-MIB not have Vlan information
if (!count($fdbs) && is_device_mib($device, 'BRIDGE-MIB'))
{

  $dot1dTpFdbEntry_table = snmpwalk_cache_oid($device, 'dot1dTpFdbPort', array(), 'BRIDGE-MIB', NULL, OBS_SNMP_ALL_TABLE);

  if (snmp_status())
  {
    $dot1dTpFdbEntry_table = snmpwalk_cache_oid($device, 'dot1dTpFdbStatus', $dot1dTpFdbEntry_table, 'BRIDGE-MIB', NULL, OBS_SNMP_ALL_TABLE);
    print_debug_vars($dot1dTpFdbEntry_table);


    $vlan = 0; // BRIDGE-MIB not have Vlan information
    foreach($dot1dTpFdbEntry_table as $mac => $entry)
    {
      $mac = mac_zeropad($mac);

      $port = $port_ifIndex_table[$entry['dot1dTpFdbPort']];
      $data = array();

      $data['port_id']    = $port['port_id'];
      $data['port_index'] = $entry['dot1dTpFdbPort'];
      $data['fdb_status'] = $entry['dot1dTpFdbStatus'];

      $fdbs[$vlan][$mac]  = $data;
    }

  }
}

/******************************
 * Process walked FDB entries *
 ******************************/

print_debug_vars($fdbs);

$fdb_count  = array('ports' => array(), // Per port FDB count
                    'total' => 0);      // Total FDB count
$fdb_insert = array();                  // Array for insert into DB
// Loop vlans
foreach ($fdbs as $vlan => $mac_list)
{
  // Loop macs
  foreach ($mac_list as $mac => $data)
  {

    // Skip incorrect mac entries
    if (strlen($mac) !== 12 || $mac == '000000000000')
    {
      //unset($fdbs[$vlan][$mac]);
      continue;
    }

    $port_id    = $data['port_id'];
    $port       = $port_table[$port_id];
    $fdb_status = $data['fdb_status'];

    $table_row = array();
    $table_row[] = $vlan;
    $table_row[] = $mac;
    $table_row[] = $port['port_label_short'];
    $table_row[] = $data['port_id'];
    $table_row[] = $data['fdb_status'];
    $table_rows[] = $table_row;
    unset($table_row);

    // if entry already exists
    if (!is_array($fdbs_db[$vlan][$mac]))
    {
      $q_update = array('device_id'   => $device['device_id'],
                        'vlan_id'     => $vlan,
                        'port_id'     => $port_id,
                        'mac_address' => $mac,
                        'fdb_status'  => $fdb_status);

      if (!is_numeric($port_id))
      {
        $q_update['port_id'] = array('NULL');
      }
      $fdb_insert[] = $q_update;
      //dbInsert($q_update, 'vlans_fdb');
      //echo('+');
    } else {
      $q_update = array();
      // if port/status are different, build an update array and update the db
      if ($fdbs_db[$vlan][$mac]['port_id'] != $port_id)
      {
        if (is_numeric($port_id))
        {
          $q_update['port_id'] = $port_id;
        } else {
          $q_update['port_id'] = array('NULL');
        }
      }
      if ($fdb_status && $fdbs_db[$vlan][$mac]['fdb_status'] != $fdb_status)
      {
        $q_update['fdb_status'] = $fdb_status;
      }
      if (count($q_update))
      {
        if (isset($fdbs_db[$vlan][$mac]['fdb_id']))
        {
          dbUpdate($q_update, 'vlans_fdb', '`fdb_id` = ?', array($fdbs_db[$vlan][$mac]['fdb_id']));
        } else {
          // Compatability
          dbUpdate($q_update, 'vlans_fdb', '`device_id` = ? AND `vlan_id` = ? AND `mac_address` = ?', array($device['device_id'], $vlan, $mac));
        }
        //echo('U');
      } else {
      }
      // remove it from the existing list
      unset ($fdbs_db[$vlan][$mac]);
    }

    $fdb_count['total']++; // Total FDB count
    if (is_numeric($port_id))
    {
      $fdb_count['ports'][$port_id]++; // Per port FDB count
    }
    //echo(PHP_EOL);
  }
}

// Delete before insert new entries (for do not show possible duplicates)
// Loop the existing list and delete anything remaining
$fdb_delete = array();
foreach ($fdbs_db as $vlan => $fdb_macs)
{
  foreach ($fdb_macs as $mac => $data)
  {
    $table_row = array();
    $table_row[] = $vlan;
    $table_row[] = $mac;
    //$table_row[] = $data['port_label_short'];
    $table_row[] = $data['port_id'];
    //$table_row[] = $fdb_port;
    //$table_row[] = $data['ifIndex'];
    $table_row[] = '%rdeleted%n';
    $table_rows[] = $table_row;
    //echo(str_pad($vlan, 8) . ' | ' . str_pad($mac,12) . ' | ' .  str_pad($data['port_id'],25) .' | '. str_pad($data['fdb_status'],16));
    //echo("-\n");
    if (isset($data['fdb_id']))
    {
      // Multi delete (for faster loop)
      $fdb_delete[] = $data['fdb_id'];
    } else {
      dbDelete('vlans_fdb', '`device_id` = ? AND `vlan_id` = ? AND `mac_address` = ?', array($device['device_id'], $vlan, $mac));
    }
  }
}

// MultiDelete old entries
if (count($fdb_delete))
{
  print_debug_vars($fdb_delete);
  dbDelete('vlans_fdb', generate_query_values($fdb_delete, 'fdb_id', NULL, FALSE));
}

// MultiInsert new fdb entries
if (count($fdb_insert))
{
  print_debug_vars($fdb_insert);
  dbInsertMulti($fdb_insert, 'vlans_fdb');
}

// FDB count for HP ProCurve
if (!$fdb_count['total'] && is_device_mib($device, 'STATISTICS-MIB'))
{
  $fdb_count['total'] = snmp_get_oid($device, 'hpSwitchFdbAddressCount.0', 'STATISTICS-MIB');
}

if (is_numeric($fdb_count['total']) && $fdb_count['total'] > 0)
{
  rrdtool_update_ng($device, 'fdb_count', array('value' => $fdb_count['total']));
  $graphs['fdb_count'] = TRUE;
}

$alert_metrics['fdb_count'] = $fdb_count['total']; // Append fdb count to device metrics

if (is_module_enabled($device, 'ports_fdbcount'))
{
  foreach ($fdb_count['ports'] as $port_id => $count)
  {
    if (!isset($port_table[$port_id]))
    {
      print_debug("No entry in port table for $port_id");
      continue;
    }
    $port = $port_table[$port_id];

    rrdtool_update_ng($device, 'port-fdbcount', array('value' => $count), get_port_rrdindex($port));
  }
}

// print_cli_table($table_rows, array('%WVLAN%n', '%WMAC Address%n', '%WPort%n', '%WPort ID%n', '%WFDB Port%n', '%WifIndex%n', '%WStatus%n'));

// Dont' print since the table can get huge and quite slow.
print_cli_table($table_rows, array('%WVLAN%n', '%WMAC Address%n', '%WPort Name%n', '%WPort ID%n', '%WStatus%n'));

// Clean
unset($fdbs_db, $fdb, $fdb_count, $fdb_insert, $fdb_update, $fdb_delete,
      $table_rows, $port_ifIndex_table, $port_table);

// EOF
