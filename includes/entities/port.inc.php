<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 *
 */

// Get port id  by ip address (using cache)
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_id_by_ip_cache($device, $ip)
{
  global $cache;

  $ip_version = get_ip_version($ip);

  if (is_array($device) && isset($device['device_id']))
  {
    $device_id = $device['device_id'];
  }
  elseif (is_numeric($device))
  {
    $device_id = $device;
  }
  if (!isset($device_id) || !$ip_version)
  {
    print_error("Invalid arguments passed into function get_port_id_by_ip_cache(). Please report to developers.");
    return FALSE;
  }

  if ($ip_version == 6)
  {
    $ip = Net_IPv6::uncompress($ip, TRUE);
  }

  if (isset($cache['port_ip'][$device_id][$ip]))
  {
    return $cache['port_ip'][$device_id][$ip];
  }

  $ips = dbFetchRows('SELECT `port_id`, `ifOperStatus`, `ifAdminStatus` FROM `ipv'.$ip_version.'_addresses`
                      LEFT JOIN `ports` USING(`port_id`)
                      WHERE `deleted` = 0 AND `device_id` = ? AND `ipv'.$ip_version.'_address` = ?', array($device_id, $ip));
  if (count($ips) === 1)
  {
    // Simple
    $port = current($ips);
    //return $port['port_id'];
  } else {
    foreach ($ips as $entry)
    {
      if ($entry['ifAdminStatus'] == 'up' && $entry['ifOperStatus'] == 'up')
      {
        // First UP entry
        $port = $entry;
        break;
      }
      elseif ($entry['ifAdminStatus'] == 'up')
      {
        // Admin up, but port down or other state
        $ips_up[]   = $entry;
      } else {
        // Admin down
        $ips_down[] = $entry;
      }
    }
    if (!isset($port))
    {
      if ($ips_up)
      {
        $port = current($ips_up);
      } else {
        $port = current($ips_down);
      }
    }
  }
  $cache['port_ip'][$device_id][$ip] = $port['port_id'] ? $port['port_id'] : FALSE;

  return $cache['port_ip'][$device_id][$ip];

}

function get_port_by_ent_index($device, $entPhysicalIndex, $allow_snmp = FALSE)
{
  $mib = 'ENTITY-MIB';
  if (!is_numeric($entPhysicalIndex) ||
    !is_numeric($device['device_id']) ||
    !is_device_mib($device, $mib))
  {
    return FALSE;
  }

  $allow_snmp = $allow_snmp || is_cli(); // Allow snmpwalk queries in poller/discovery or if in wui passed TRUE!

  if (isset($GLOBALS['cache']['snmp'][$mib][$device['device_id']]))
  {
    // Cached
    $entity_array = $GLOBALS['cache']['snmp'][$mib][$device['device_id']];
    if (empty($entity_array))
    {
      // Force DB queries
      $allow_snmp = FALSE;
    }
  }
  elseif ($allow_snmp)
  {
    // Inventory module disabled, this DB empty, try to cache
    $entity_array = array();
    $oids = array('entPhysicalDescr', 'entPhysicalName', 'entPhysicalClass', 'entPhysicalContainedIn', 'entPhysicalParentRelPos');
    if (is_device_mib($device, 'ARISTA-ENTITY-SENSOR-MIB'))
    {
      $oids[] = 'entPhysicalAlias';
    }
    foreach ($oids as $oid)
    {
      $entity_array = snmpwalk_cache_multi_oid($device, $oid, $entity_array, snmp_mib_entity_vendortype($device, 'ENTITY-MIB'));
      if (!$GLOBALS['snmp_status']) { break; }
    }
    $entity_array = snmpwalk_cache_twopart_oid($device, 'entAliasMappingIdentifier', $entity_array, 'ENTITY-MIB:IF-MIB');
    if (empty($entity_array))
    {
      // Force DB queries
      $allow_snmp = FALSE;
    }
    $GLOBALS['cache']['snmp'][$mib][$device['device_id']] = $entity_array;
  } else {
    // Or try to use DB
  }

  //print_debug_vars($entity_array);
  $sensor_index = $entPhysicalIndex; // Initial ifIndex
  $sensor_name  = '';
  do
  {
    if ($allow_snmp)
    {
      // SNMP (discovery)
      $sensor_port = $entity_array[$sensor_index];
    } else {
      // DB (web)
      $sensor_port = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalIndex` = ?', array($device['device_id'], $sensor_index));
    }
    print_debug_vars($sensor_index, 1);
    print_debug_vars($sensor_port, 1);

    if ($sensor_port['entPhysicalClass'] == 'sensor')
    {
      // Need to store initial sensor name, for multi-lane ports
      $sensor_name = $sensor_port['entPhysicalName'];
    }

    if ($sensor_port['entPhysicalClass'] === 'port')
    {
      // Port found, get mapped ifIndex
      unset($entAliasMappingIdentifier);
      foreach (array(0, 1, 2) as $i)
      {
        if (isset($sensor_port[$i]['entAliasMappingIdentifier']))
        {
          $entAliasMappingIdentifier = $sensor_port[$i]['entAliasMappingIdentifier'];
          break;
        }
      }
      if (isset($entAliasMappingIdentifier) && str_contains($entAliasMappingIdentifier, 'fIndex'))
      {
        list(, $ifIndex) = explode('.', $entAliasMappingIdentifier);

        $port = get_port_by_index_cache($device['device_id'], $ifIndex);
        if (is_array($port))
        {
          // Hola, port really found
          //$options['entPhysicalIndex_measured'] = $ifIndex;
          //$options['measured_class']  = 'port';
          //$options['measured_entity'] = $port['port_id'];
          print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port['port_id']);
          return $port;
        }
      }
      elseif (!$allow_snmp && $sensor_port['ifIndex'])
      {
        $ifIndex = $sensor_port['ifIndex'];
        $port = get_port_by_index_cache($device['device_id'], $ifIndex);
        print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port['port_id']);
        return $port;
      }

      break; // Exit do-while
    }
    elseif ($device['os'] == 'arista_eos' && $sensor_port['entPhysicalClass'] == 'container' && strlen($sensor_port['entPhysicalAlias']))
    {
      // Arista not have entAliasMappingIdentifier, but used entPhysicalAlias as ifDescr
      $port_id = get_port_id_by_ifDescr($device['device_id'], $sensor_port['entPhysicalAlias']);
      if (is_numeric($port_id))
      {
        // Hola, port really found
        $port    = get_port_by_id($port_id);
        $ifIndex = $port['ifIndex'];
        //$options['entPhysicalIndex_measured'] = $ifIndex;
        //$options['measured_class']  = 'port';
        //$options['measured_entity'] = $port_id;
        print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port_id);
        return $port;
        //break; // Exit do-while
      }
      $sensor_index = $sensor_port['entPhysicalContainedIn']; // Next ifIndex
    }
    elseif ($sensor_index == $sensor_port['entPhysicalContainedIn'])
    {
      break; // Break if current index same as next to avoid loop
    } else {
      $sensor_index = $sensor_port['entPhysicalContainedIn']; // Next ifIndex

      // See: http://jira.observium.org/browse/OBS-2295
      // IOS-XE and IOS-XR can store in module index both: sensors and port
      $sensor_transceiver = $sensor_port['entPhysicalClass'] == 'sensor' &&
        str_icontains($sensor_port['entPhysicalName'] . $sensor_port['entPhysicalDescr'] . $sensor_port['entPhysicalVendorType'], array('transceiver', '-PORT-'));
      // This is multi-lane optical transceiver, ie 100G, 40G, multiple sensors for each port
      $sensor_multilane   = $sensor_port['entPhysicalClass'] == 'container' &&
        (in_array($sensor_port['entPhysicalVendorType'], ['cevContainer40GigBasePort', 'cevContainerCXP', 'cevContainerCPAK', ]) || // Known Cisco specific containers
          str_contains($sensor_port['entPhysicalName'] . $sensor_port['entPhysicalDescr'], array('Optical')));
      if ($sensor_transceiver)
      {
        $tmp_index = dbFetchCell('SELECT `entPhysicalIndex` FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalContainedIn` = ? AND `entPhysicalClass` = ?', array($device['device_id'], $sensor_index, 'port'));
        if (is_numeric($tmp_index) && $tmp_index > 0)
        {
          // If port index found, try this entPhysicalIndex in next round
          $sensor_index = $tmp_index;
        }
      }
      elseif ($sensor_multilane)
      {
        $entries = dbFetchRow('SELECT `entPhysicalIndex`, `entPhysicalName` FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalContainedIn` = ? AND `entPhysicalClass` = ?', array($device['device_id'], $sensor_index, 'port'));
        if (count($entries) > 1 &&
          preg_match('/(?<start>\D{2})(?<num>\d+(?:\/\d+)+).*Lane\s*(?<lane>\d+)/', $sensor_name, $matches)) // detect port numeric part and lane
        {
          // There is each Line associated with breakout port, mostly is QSFP+ 40G
          // FortyGigE0/0/0/0-Tx Lane 1 Power -> 0/RP0-TenGigE0/0/0/0/1
          // FortyGigE0/0/0/0-Tx Lane 2 Power -> 0/RP0-TenGigE0/0/0/0/2
          $lane_num = $matches['start'] . $matches['num'] . '/' . $matches['lane']; // FortyGigE0/0/0/0-Tx Lane 1 -> gE0/0/0/0/1
          foreach ($entries as $entry)
          {
            if (str_ends($entry['entPhysicalName'], $lane_num))
            {
              $sensor_index = $entry['entPhysicalIndex'];
              break;
            }
          }

        }
        elseif (is_numeric($entries[0]['entPhysicalIndex']) && $entries[0]['entPhysicalIndex'] > 0)
        {
          // Single multi-lane port association, ie 100G
          $sensor_index = $entries[0]['entPhysicalIndex'];
        }
      }
    }
    // NOTE for self: entPhysicalParentRelPos >= 0 because on iosxr trouble
  } while ($sensor_port['entPhysicalClass'] !== 'port' && $sensor_port['entPhysicalContainedIn'] > 0 && ($sensor_port['entPhysicalParentRelPos'] >= 0 || $device['os'] == 'arista_eos'));

}

// Get port array by ifIndex (using cache)
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_by_index_cache($device, $ifIndex, $deleted = 0)
{
  global $cache;

  if (is_array($device) && isset($device['device_id']))
  {
    $device_id = $device['device_id'];
  }
  elseif (is_numeric($device))
  {
    $device_id = $device;
  }
  if (!isset($device_id) || !is_numeric($ifIndex))
  {
    print_error("Invalid arguments passed into function get_port_by_index_cache(). Please report to developers.");
  }

  if (isset($cache['port_index'][$device_id][$ifIndex]) && is_numeric($cache['port_index'][$device_id][$ifIndex]))
  {
    $id = $cache['port_index'][$device_id][$ifIndex];
  } else {
    $deleted = $deleted ? 1 : 0; // Just convert boolean to 0 or 1

    $id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ? AND `deleted` = ? LIMIT 1", array($device_id, $ifIndex, $deleted));
    if (!$deleted && is_numeric($id))
    {
      // Cache port IDs (except deleted)
      $cache['port_index'][$device_id][$ifIndex] = $id;
    }
  }

  $port = get_port_by_id_cache($id);
  if (is_array($port)) { return $port; }

  return FALSE;
}

// Get port array by ifIndex
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_by_ifIndex($device_id, $ifIndex)
{
  $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ? LIMIT 1", array($device_id, $ifIndex));

  if (is_array($port))
  {
    humanize_port($port);
    return $port;
  }

  return FALSE;
}

// Get port ID by ifDescr (i.e. 'TenGigabitEthernet1/1') or ifName (i.e. 'Te1/1')
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_id_by_ifDescr($device_id, $ifDescr, $deleted = 0)
{
  $port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE `device_id` = ? AND (`ifDescr` = ? OR `ifName` = ?) AND `deleted` = ? LIMIT 1", array($device_id, $ifDescr, $ifDescr, $deleted));

  if (is_numeric($port_id))
  {
    return $port_id;
  } else {
    return FALSE;
  }
}

// Get port ID by ifAlias (interface description)
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_id_by_ifAlias($device_id, $ifAlias, $deleted = 0)
{
  $port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE `device_id` = ? AND `ifAlias` = ? AND `deleted` = ? LIMIT 1", array($device_id, $ifAlias, $deleted));

  if (is_numeric($port_id))
  {
    return $port_id;
  } else {
    return FALSE;
  }
}

// Get port ID by customer params (see http://www.observium.org/wiki/Interface_Description_Parsing)
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_id_by_customer($customer)
{
  $where = ' WHERE 1';
  if (is_array($customer))
  {
    foreach ($customer as $var => $value)
    {
      if ($value != '')
      {
        switch ($var)
        {
          case 'device':
          case 'device_id':
            $where .= generate_query_values($value, 'device_id');
            break;
          case 'type':
          case 'descr':
          case 'circuit':
          case 'speed':
          case 'notes':
            $where .= generate_query_values($value, 'port_descr_'.$var);
            break;
        }
      }
    }
  } else {
    return FALSE;
  }

  $query = 'SELECT `port_id` FROM `ports` ' . $where . ' ORDER BY `ifOperStatus` DESC';
  $ids = dbFetchColumn($query);

  //print_vars($ids);
  switch (count($ids))
  {
    case 0:
      return FALSE;
    case 1:
      return $ids[0];
      break;
    default:
      foreach ($ids as $port_id)
      {
        $port = get_port_by_id_cache($port_id);
        $device = device_by_id_cache($port['device_id']);
        if ($device['disabled'] || !$device['status'])
        {
          continue; // switch to next ID
        }
        break;
      }
      return $port_id;
  }
  return FALSE;
}

// Delete port from database and associated rrd files
// DOCME needs phpdoc block
// TESTME needs unit testing
function delete_port($int_id, $delete_rrd = TRUE)
{
  global $config;

  $port = dbFetchRow("SELECT * FROM `ports`
                      LEFT JOIN `devices` USING (`device_id`)
                      WHERE `port_id` = ?", array($int_id));
  $ret = "> Deleted interface from ".$port['hostname'].": id=$int_id (".$port['ifDescr'].")\n";

  // Remove entities from common tables
  $deleted_entities = array();
  foreach ($config['entity_tables'] as $table)
  {
    $where = '`entity_type` = ?' . generate_query_values($int_id, 'entity_id');
    $table_status = dbDelete($table, $where, array('port'));
    if ($table_status) { $deleted_entities['port'] = 1; }
  }
  if (count($deleted_entities))
  {
    $ret .= ' * Deleted common entity entries linked to port.' . PHP_EOL;
  }

  // FIXME, move to definitions
  $port_tables = array('eigrp_ports', 'ipv4_addresses', 'ipv6_addresses',
                       'ip_mac', 'juniAtmVp', 'mac_accounting', 'ospf_nbrs', 'ospf_ports',
                       'ports_adsl', 'ports_cbqos', 'ports_vlans', 'pseudowires', 'vlans_fdb',
                       'neighbours', 'ports');
  $deleted_tables = [];
  foreach ($port_tables as $table)
  {
    $table_status = dbDelete($table, "`port_id` = ?", array($int_id));
    if ($table_status) { $deleted_tables[] = $table; }
  }

  $table_status = dbDelete('ports_stack', "`port_id_high` = ?  OR `port_id_low` = ?",    array($int_id, $int_id));
  if ($table_status) { $deleted_tables[] = 'ports_stack'; }
  $table_status = dbDelete('entity_permissions', "`entity_type` = 'port' AND `entity_id` = ?", array($int_id));
  if ($table_status) { $deleted_tables[] = 'entity_permissions'; }
  $table_status = dbDelete('alert_table', "`entity_type` = 'port' AND `entity_id` = ?", array($int_id));
  if ($table_status) { $deleted_tables[] = 'alert_table'; }
  $table_status = dbDelete('group_table', "`entity_type` = 'port' AND `entity_id` = ?", array($int_id));
  if ($table_status) { $deleted_tables[] = 'group_table'; }

  $ret .= ' * Deleted interface entries from tables: '.implode(', ', $deleted_tables).PHP_EOL;

  if ($delete_rrd)
  {
    $rrd_types = array('adsl', 'dot3', 'fdbcount', 'poe', NULL);
    $deleted_rrds = [];
    foreach ($rrd_types as $type)
    {
      $rrdfile = get_port_rrdfilename($port, $type, TRUE);
      if (is_file($rrdfile))
      {
        unlink($rrdfile);
        $deleted_rrds[] = $rrdfile;
      }
    }
    $ret .= ' * Deleted interface RRD files: ' . implode(', ', $deleted_rrds) . PHP_EOL;
  }

  return $ret;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_rrdindex($port)
{
  global $config;

  $device = device_by_id_cache($port['device_id']);

  $device_identifier = strtolower($config['os'][$device['os']]['port_rrd_identifier']);

  // default to ifIndex
  $this_port_identifier = $port['ifIndex'];

  if ($device_identifier == "ifname" && $port['ifName'] != "")
  {
    $this_port_identifier = strtolower(str_replace("/", "-", $port['ifName']));
  }

  return $this_port_identifier;
}

// CLEANME DEPRECATED
function get_port_rrdfilename($port, $suffix = NULL, $fullpath = FALSE)
{
  $this_port_identifier = get_port_rrdindex($port);

  if ($suffix == "")
  {
    $filename = "port-" . $this_port_identifier . ".rrd";
  } else {
    $filename = "port-" . $this_port_identifier . "-" . $suffix . ".rrd";
  }

  if ($fullpath)
  {
    $device   = device_by_id_cache($port['device_id']);
    $filename = get_rrd_path($device, $filename);
  }

  return $filename;
}

// EOF