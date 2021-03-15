<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
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

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_id_by_mac($device, $mac)
{
  if (is_array($device) && isset($device['device_id']))
  {
    $device_id = $device['device_id'];
  }
  elseif (is_numeric($device))
  {
    $device_id = $device;
  }

  $remote_mac = mac_zeropad($mac);
  if ($remote_mac && $remote_mac != '000000000000')
  {
    return dbFetchCell("SELECT `port_id` FROM `ports` WHERE `deleted` = '0' AND `ifPhysAddress` = ? AND `device_id` = ? LIMIT 1", [ $remote_mac, $device_id ]);
  }

  return FALSE;
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
      $sensor_port = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalIndex` = ? AND `deleted` IS NULL', array($device['device_id'], $sensor_index));
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
      if (isset($entAliasMappingIdentifier) && str_exists($entAliasMappingIdentifier, 'fIndex'))
      {
        list(, $ifIndex) = explode('.', $entAliasMappingIdentifier);

        $port = get_port_by_index_cache($device['device_id'], $ifIndex);
        if (is_array($port))
        {
          // Hola, port really found
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
        print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port_id);
        return $port; // Exit do-while
      }
      $sensor_index = $sensor_port['entPhysicalContainedIn']; // Next ifIndex
    }
    elseif ($sensor_index == $sensor_port['entPhysicalContainedIn'])
    {
      break; // Break if current index same as next to avoid loop
    }
    elseif ($sensor_port['entPhysicalClass'] == 'module' &&
            (isset($sensor_port[0]['entAliasMappingIdentifier']) || isset($sensor_port[1]['entAliasMappingIdentifier']) || isset($sensor_port[2]['entAliasMappingIdentifier'])))
    {
      // Cisco IOSXR 6.5.x ASR 9900 platform && NCS 5500
      $sensor_index = $sensor_port['entPhysicalContainedIn']; // Next ifIndex

      // By first try if entAliasMappingIdentifier correct
      unset($entAliasMappingIdentifier);
      foreach (array(0, 1, 2) as $i)
      {
        if (isset($sensor_port[$i]['entAliasMappingIdentifier']))
        {
          $entAliasMappingIdentifier = $sensor_port[$i]['entAliasMappingIdentifier'];
          break;
        }
      }
      if (isset($entAliasMappingIdentifier) && str_exists($entAliasMappingIdentifier, 'fIndex'))
      {
        list(, $ifIndex) = explode('.', $entAliasMappingIdentifier);

        $port = get_port_by_index_cache($device['device_id'], $ifIndex);
        if (is_array($port))
        {
          // Hola, port really found
          print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port['port_id']);
          return $port;
        }
      }

      // This case for Cisco IOSXR ASR 9900 platform, when have incorrect entAliasMappingIdentifier association,
      // https://jira.observium.org/browse/OBS-3147
      $port_id = FALSE;
      if (str_exists($sensor_port['entPhysicalName'], '-PORT-'))
      {
        // Second, try detect port by entPhysicalDescr/entPhysicalName
        if (str_starts($sensor_port['entPhysicalDescr'], [ '10GBASE', '10GE' ]) ||
            str_iexists($sensor_port['entPhysicalDescr'], [ ' 10GBASE', ' 10GE', ' 10G ' ]))
        {
          $ifDescr_base = 'TenGigE';
        }
        elseif (str_starts($sensor_port['entPhysicalDescr'], [ '25GBASE', '25GE' ]) ||
                str_iexists($sensor_port['entPhysicalDescr'], [ ' 25GBASE', ' 25GE', ' 25G ' ]))
        {
          $ifDescr_base = 'TwentyFiveGigE';
        }
        elseif (str_starts($sensor_port['entPhysicalDescr'], [ '40GBASE', '40GE' ]) ||
                str_iexists($sensor_port['entPhysicalDescr'], [ ' 40GBASE', ' 40GE', ' 40G ' ]))
        {
          $ifDescr_base = 'FortyGigE';
        }
        elseif (str_starts($sensor_port['entPhysicalDescr'], [ '100GBASE', '100GE' ]) ||
                str_iexists($sensor_port['entPhysicalDescr'], [ ' 100GBASE', ' 100GE', ' 100G ' ]))
        {
          // Ie:
          // Cisco CPAK 100GBase-SR4, 100m, MMF
          // 100GBASE-ER4 CFP2 Module for SMF (<40 km)
          // Non-Cisco QSFP28 100G ER4 Pluggable Optics Module
          $ifDescr_base = 'HundredGigE';
        }
        $ifDescr_num = str_replace('-PORT-', '/', $sensor_port['entPhysicalName']);
        $port_id = get_port_id_by_ifDescr($device['device_id'], $ifDescr_base . $ifDescr_num);
        if (!is_numeric($port_id))
        {
          // FIXME, I think first node number '0/' should be detected by some how
          $port_id = get_port_id_by_ifDescr($device['device_id'], $ifDescr_base . '0/' . $ifDescr_num);
        }
      }
      elseif (str_exists($sensor_port['entPhysicalName'], [ 'TenGigE', 'TwentyFiveGigE', 'FortyGigE', 'HundredGigE' ]))
      {
        // Same as previous, but on NCS platform and entPhysicalName contain correct ifDescr, ie:
        // FortyGigE0/0/0/20
        $ifDescr = $sensor_port['entPhysicalName'];
        $port_id = get_port_id_by_ifDescr($device['device_id'], $ifDescr);
      }

      if (is_numeric($port_id))
      {
        // Hola, port really found
        $port = get_port_by_id($port_id);
        $ifIndex = $port['ifIndex'];
        print_debug("Port is found: ifIndex = $ifIndex, port_id = " . $port_id);

        return $port;
      }

    } else {
      $sensor_index = $sensor_port['entPhysicalContainedIn']; // Next ifIndex

      // See: http://jira.observium.org/browse/OBS-2295
      // IOS-XE and IOS-XR can store in module index both: sensors and port
      $sensor_transceiver = $sensor_port['entPhysicalClass'] == 'sensor' &&
                            str_iexists($sensor_port['entPhysicalName'] . $sensor_port['entPhysicalDescr'] . $sensor_port['entPhysicalVendorType'], array( 'transceiver', '-PORT-'));
      // This is multi-lane optical transceiver, ie 100G, 40G, multiple sensors for each port
      $sensor_multilane   = $sensor_port['entPhysicalClass'] == 'container' &&
                            (in_array($sensor_port['entPhysicalVendorType'], [ 'cevContainer40GigBasePort', 'cevContainerCXP', 'cevContainerCPAK' ]) || // Known Cisco specific containers
                             str_exists($sensor_port['entPhysicalName'] . $sensor_port['entPhysicalDescr'], array( 'Optical')));                       // Pluggable Optical Module Container
      if ($sensor_transceiver)
      {
        $tmp_index = dbFetchCell('SELECT `entPhysicalIndex` FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalContainedIn` = ? AND `entPhysicalClass` = ? AND `deleted` IS NULL', array($device['device_id'], $sensor_index, 'port'));
        if (is_numeric($tmp_index) && $tmp_index > 0)
        {
          // If port index found, try this entPhysicalIndex in next round
          $sensor_index = $tmp_index;
        }
      }
      elseif ($sensor_multilane)
      {
        $entries = dbFetchRows('SELECT `entPhysicalIndex`, `entPhysicalName` FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalContainedIn` = ? AND `entPhysicalClass` = ? AND `deleted` IS NULL', array($device['device_id'], $sensor_index, 'port'));
        print_debug("Multi-Lane entries:");
        print_debug_vars($entries, 1);
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

// Get port array by ID (using cache)
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_by_id_cache($port_id)
{
  return get_entity_by_id_cache('port', $port_id);
}

// Get port array by ID (with port state)
// NOTE get_port_by_id(ID) != get_port_by_id_cache(ID)
// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_by_id($port_id)
{
  if (is_numeric($port_id))
  {
    //$port = dbFetchRow("SELECT * FROM `ports` LEFT JOIN `ports-state` ON `ports`.`port_id` = `ports-state`.`port_id`  WHERE `ports`.`port_id` = ?", array($port_id));
    $port = dbFetchRow("SELECT * FROM `ports`  WHERE `ports`.`port_id` = ?", array($port_id));
  }

  if (is_array($port))
  {
    //$port['port_id'] = $port_id; // It corrects the situation, when `ports-state` is empty
    humanize_port($port);
    return $port;
  }

  return FALSE;
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

// DOCME needs phpdoc block
// TESTME needs unit testing
function is_port_valid($port, $device)
{
  global $config;

  // Ignore non standard ifOperStatus
  // See http://tools.cisco.com/Support/SNMP/do/BrowseOID.do?objectInput=ifOperStatus
  $valid_ifOperStatus = [ 'testing', 'dormant', 'down', 'lowerLayerDown', 'unknown', 'up', 'monitoring' ];
  if (isset($port['ifOperStatus']) && strlen($port['ifOperStatus']) &&
      !in_array($port['ifOperStatus'], $valid_ifOperStatus))
  {
    print_debug("ignored (by ifOperStatus = notPresent or invalid value).");
    return FALSE;
  }

  // Ignore ports with empty ifType
  $ports_allow_empty = isset($config['os'][$device['os']]['ports_ignore']['allow_empty']) &&
                       $config['os'][$device['os']]['ports_ignore']['allow_empty'];
  if (!isset($port['ifType']) && !$ports_allow_empty)
  {
    /* Some devices (ie D-Link) report ports without any useful info, example:
    [74] => Array
        (
            [ifName] => po22
            [ifInMulticastPkts] => 0
            [ifInBroadcastPkts] => 0
            [ifOutMulticastPkts] => 0
            [ifOutBroadcastPkts] => 0
            [ifLinkUpDownTrapEnable] => enabled
            [ifHighSpeed] => 0
            [ifPromiscuousMode] => false
            [ifConnectorPresent] => false
            [ifAlias] => po22
            [ifCounterDiscontinuityTime] => 0:0:00:00.00
        )
    */
    print_debug("ignored (by empty ifType).");
    return FALSE;
  }

  // This happen on some liebert UPS devices or when device have memory leak (ie Eaton Powerware)
  if (isset($config['os'][$device['os']]['ifType_ifDescr']) && $config['os'][$device['os']]['ifType_ifDescr'] && $port['ifIndex'])
  {
    $len = strlen($port['ifDescr']);
    $type = rewrite_iftype($port['ifType']);
    if ($type && ($len === 0 || $len > 255 ||
                  isHexString($port['ifDescr']) ||
                  preg_match('/(.)\1{4,}/', $port['ifDescr'])))
    {
      $port['ifDescr'] = $type . ' ' . $port['ifIndex'];
    }
  }

  //$if = ($config['os'][$device['os']]['ifname'] ? $port['ifName'] : $port['ifDescr']);
  $valid_ifDescr = strlen($port['ifDescr']);
  $valid_ifName  = strlen($port['ifName']);

  // Ignore ports with empty ifName and ifDescr (while not possible store in db)
  if (!$valid_ifDescr && !$valid_ifName)
  {
    print_debug("ignored (by empty ifDescr and ifName).");
    return FALSE;
  }

  // FIXME. Prefer regexp
  foreach ((array)$config['bad_if'] as $bi)
  {
    if (stripos($port['ifDescr'], $bi) !== FALSE)
    {
      print_debug("ignored (by ifDescr): ".$port['ifDescr']." [ $bi ]");
      return FALSE;
    }
    elseif ($valid_ifName && stripos($port['ifName'], $bi) !== FALSE)
    {
      print_debug("ignored (by ifName): ".$port['ifName']." [ $bi ]");
      return FALSE;
    }
  }

  $ports_ignore_alias = isset($config['os'][$device['os']]['ports_ignore']['alias_regex']) ? (array)$config['os'][$device['os']]['ports_ignore']['alias_regex'] : [];
  if (isset($config['bad_ifalias_regexp']))
  {
    $ports_ignore_alias = array_merge($ports_ignore_alias, (array)$config['bad_ifalias_regexp']);
  }
  foreach ($ports_ignore_alias as $bi)
  {
    if (preg_match($bi, $port['ifAlias']))
    {
      print_debug("ignored (by ifAlias): ".$port['ifAlias']." [ $bi ]");
      return FALSE;
    }
  }

  // Ignore ports by ifName/ifDescr (do not forced as case insensitive)
  $ports_ignore_label = isset($config['os'][$device['os']]['ports_ignore']['label_regex']) ? (array)$config['os'][$device['os']]['ports_ignore']['label_regex'] : [];
  if (isset($config['bad_if_regexp']))
  {
    $ports_ignore_label = array_merge($ports_ignore_label, (array)$config['bad_if_regexp']);
  }
  foreach ($ports_ignore_label as $bi)
  {
    if (preg_match($bi, $port['ifDescr']))
    {
      print_debug("ignored (by ifDescr regexp): ".$port['ifDescr']." [ $bi ]");
      return FALSE;
    }
    elseif ($valid_ifName && preg_match($bi, $port['ifName']))
    {
      print_debug("ignored (by ifName regexp): ".$port['ifName']." [ $bi ]");
      return FALSE;
    }
  }

  // Ignore ports by ifType
  $ports_ignore_type = isset($config['os'][$device['os']]['ports_ignore']['type']) ? (array)$config['os'][$device['os']]['ports_ignore']['type'] : [];
  if (isset($config['bad_iftype']))
  {
    $ports_ignore_type = array_merge($ports_ignore_type, (array)$config['bad_iftype']);
  }
  foreach ($ports_ignore_type as $bi)
  {
    if (strpos($port['ifType'], $bi) !== FALSE)
    {
      print_debug("ignored (by ifType): ".$port['ifType']." [ $bi ]");
      return FALSE;
    }
  }

  return TRUE;
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
function port_html_class($ifOperStatus, $ifAdminStatus, $encrypted = FALSE)
{
  $ifclass = "interface-upup";
  if      ($ifAdminStatus == "down")           { $ifclass = "gray"; }
  elseif  ($ifAdminStatus == "up")
  {
    if     ($ifOperStatus == "down")           { $ifclass = "red"; }
    elseif ($ifOperStatus == "lowerLayerDown") { $ifclass = "orange"; }
    elseif ($ifOperStatus == "monitoring")     { $ifclass = "green"; }
    //elseif ($encrypted === '1')                { $ifclass = "olive"; }
    elseif ($encrypted)                        { $ifclass = "olive"; }
    elseif ($ifOperStatus == "up")             { $ifclass = ""; }
    else                                       { $ifclass = "purple"; }
  }

  return $ifclass;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_port_rrdindex($port)
{
  global $config;

  if (isset($port['device_id']))
  {
    $device_id = $port['device_id'];
  } else {
    // In poller, device_id not always passed
    $port_tmp = get_port_by_id_cache($port['port_id']);
    $device_id = $port_tmp['device_id'];
  }
  $device = device_by_id_cache($device_id);

  if (isset($config['os'][$device['os']]['port_rrd_identifier']))
  {
    $device_identifier = strtolower($config['os'][$device['os']]['port_rrd_identifier']);
  } else {
    $device_identifier = 'ifindex';
  }

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
    if (isset($port['device_id']))
    {
      $device_id = $port['device_id'];
    } else {
      // In poller, device_id not always passed
      $port_tmp = get_port_by_id_cache($port['port_id']);
      $device_id = $port_tmp['device_id'];
    }
    $device   = device_by_id_cache($device_id);
    $filename = get_rrd_path($device, $filename);
  }

  return $filename;
}

// EOF