<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

// lldpRemoteSystemsData: lldpRemTable + lldpRemManAddrTable + lldpRemUnknownTLVTable + lldpRemOrgDefInfoTable
$lldpRemTable_oids = array('lldpRemChassisIdSubtype', 'lldpRemChassisId',
                           'lldpRemPortIdSubtype', 'lldpRemPortId', 'lldpRemPortDesc',
                           'lldpRemSysName');
$lldp_array = array();
foreach ($lldpRemTable_oids as $oid) {
  //$lldp_array = snmpwalk_cache_threepart_oid($device, $oid, $lldp_array, "LLDP-MIB");
  $lldp_array = snmpwalk_cache_oid($device, $oid, $lldp_array, "LLDP-MIB");

  if (empty($lldp_array)) { break; } // Stop walk if no data
}

if ($lldp_array) {
  // lldpRemSysDesc can be multiline
  //$lldp_array = snmpwalk_cache_threepart_oid($device, 'lldpRemPortDesc', $lldp_array, "LLDP-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
  //$lldp_array = snmpwalk_cache_threepart_oid($device, 'lldpRemSysDesc', $lldp_array, "LLDP-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
  $lldp_array = snmpwalk_cache_oid($device, 'lldpRemSysDesc', $lldp_array, "LLDP-MIB", NULL, OBS_SNMP_ALL_MULTILINE);

  if (is_device_mib($device, 'LLDP-EXT-MED-MIB')) {
    // See Cumulus Linux
    // not exist lldpRemSysName, lldpRemSysDesc
    $lldp_array = snmpwalk_cache_oid($device, 'lldpXMedRemSoftwareRev', $lldp_array, "LLDP-EXT-MED-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
    $lldp_array = snmpwalk_cache_oid($device, 'lldpXMedRemMfgName',     $lldp_array, "LLDP-EXT-MED-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
    $lldp_array = snmpwalk_cache_oid($device, 'lldpXMedRemModelNam',    $lldp_array, "LLDP-EXT-MED-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
  }

  // lldpRemManAddrTable
  // Case 1:
  // LLDP-MIB::lldpRemManAddrSubtype.120.30001.1582.1.4.10.133.3.10 = INTEGER: ipV4(1)
  // LLDP-MIB::lldpRemManAddr.120.30001.1582.1.4.10.133.3.10 = Hex-STRING: 0A 85 03 0A
  // LLDP-MIB::lldpRemManAddrIfSubtype.120.30001.1582.1.4.10.133.3.10 = INTEGER: ifIndex(2)
  // LLDP-MIB::lldpRemManAddrIfId.120.30001.1582.1.4.10.133.3.10 = INTEGER: 2009
  // LLDP-MIB::lldpRemManAddrOID.120.30001.1582.1.4.10.133.3.10 = OID: SNMPv2-SMI::zeroDotZero.0
  // Case 2:
  // LLDP-MIB::lldpRemManAddrIfSubtype.1173570000.129.2.1.4.10.0.10.5 = INTEGER: unknown(1)
  // LLDP-MIB::lldpRemManAddrIfSubtype.1173834000.4.6.0.6.132.181.156.89.235.128 = INTEGER: unknown(1)
  // LLDP-MIB::lldpRemManAddrIfId.1173570000.129.2.1.4.10.0.10.5 = INTEGER: 0
  // LLDP-MIB::lldpRemManAddrIfId.1173834000.4.6.0.6.132.181.156.89.235.128 = INTEGER: 0
  // LLDP-MIB::lldpRemManAddrOID.1173570000.129.2.1.4.10.0.10.5 = OID: SNMPv2-SMI::enterprises.14823.2.2.1.2.1.1
  // LLDP-MIB::lldpRemManAddrOID.1173834000.4.6.0.6.132.181.156.89.235.128 = OID: SNMPv2-SMI::enterprises.14823.2.2.1.2.1.7
  // Case 3:
  // LLDP-MIB::lldpRemManAddrIfId.0.2.1.1.4.10.137.41.4 = INTEGER: 0
  // LLDP-MIB::lldpRemManAddrIfId.0.3.1.1.4.10.137.41.57 = INTEGER: 2009
  // LLDP-MIB::lldpRemManAddrIfId.0.47.1.1.4.10.137.41.19 = INTEGER: 2009
  // LLDP-MIB::lldpRemManAddrIfId.0.49.3.1.4.10.129.2.171 = INTEGER: 34
  // LLDP-MIB::lldpRemManAddrIfId.0.49.3.2.16.42.2.32.40.255.0.0.0.0.0.0.1.0.0.1.113 = INTEGER: 34
  // LLDP-MIB::lldpRemManAddrIfId.0.49.4.1.4.10.129.2.171 = INTEGER: 19
  // LLDP-MIB::lldpRemManAddrIfId.0.49.4.2.16.42.2.32.40.255.0.0.0.0.0.0.1.0.0.1.113 = INTEGER: 19
  // LLDP-MIB::lldpRemManAddrIfId.0.53.2.1.4.10.129.2.171 = INTEGER: 23
  // LLDP-MIB::lldpRemManAddrIfId.0.53.2.2.16.42.2.32.40.255.0.0.0.0.0.0.1.0.0.1.113 = INTEGER: 23
  // Case 4:
  // LLDP-MIB::lldpRemManAddrSubtype.22 = INTEGER: ipV4(1)
  // LLDP-MIB::lldpRemManAddrSubtype.86 = INTEGER: ipV6(2)
  // LLDP-MIB::lldpRemManAddr.22 = STRING: "10.31.0.2"
  // LLDP-MIB::lldpRemManAddr.86 = STRING: "fe80::d6ca:6dff:fe8e:8b3f"
  // Case 5 (multiple IP addresses):
  // lldpRemManAddrIfId.31300.2.2.1.4.192.168.13.1 = 5
  // lldpRemManAddrIfId.31300.2.2.2.16.32.1.4.112.0.40.11.253.0.0.0.0.0.0.0.0 = 5
  // lldpRemManAddrIfId.31300.2.2.2.16.254.128.0.0.0.0.0.0.198.173.52.255.254.216.108.126 = 5
  $lldp_addr = snmpwalk_cache_oid($device, 'lldpRemManAddrIfId',         [], "LLDP-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
  $lldp_addr = snmpwalk_cache_oid($device,     'lldpRemManAddr', $lldp_addr, "LLDP-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

  foreach ($lldp_addr as $index => $entry) {
    if (isset($lldp_array[$index])) {
      $lldp_array[$index] = array_merge($lldp_array[$index], $entry);
      if (isset($entry['lldpRemManAddr'])) {
        $addr = hex2ip($entry['lldpRemManAddr']);
        $lldp_array[$index]['lldpRemMan'][$addr] = $entry; // For multiple entries
      }
      continue;
    }
    $index_array = explode('.', $index);
    // LLDP index
    $lldpRemTimeMark     = array_shift($index_array);
    $lldpRemLocalPortNum = array_shift($index_array);
    $lldpRemIndex        = array_shift($index_array);
    $lldp_index          = "$lldpRemTimeMark.$lldpRemLocalPortNum.$lldpRemIndex";
    if (!isset($lldp_array[$lldp_index])) {
      continue;
    }
    $lldp_array[$lldp_index] = array_merge($lldp_array[$lldp_index], $entry);

    // Already exist Oid, just merge
    // if (isset($entry['lldpRemManAddr']))
    // {
    //   continue;
    // }

    // Convert from index part
    $lldpAddressFamily   = array_shift($index_array);
    $len                 = array_shift($index_array);
    $addr                = implode('.', $index_array);
    if (isset($entry['lldpRemManAddr'])) {
      // Already exist Oid, just merge
      $addr = hex2ip($entry['lldpRemManAddr']);
      //continue;
    } elseif ($lldpAddressFamily == 1 || $len == 4) {
      // IPv4, ie: 4.10.129.2.171
      $lldp_array[$lldp_index]['lldpRemManAddr'] = $addr;
    } elseif ($lldpAddressFamily == 2 || $len == 16) {
      // IPv6, ie: 16.42.2.32.40.255.0.0.0.0.0.0.1.0.0.1.113
      $addr = snmp2ipv6($addr);
      $lldp_array[$lldp_index]['lldpRemManAddr'] = $addr;
    } elseif ($lldpAddressFamily == 0 && $len == 6) {
      // Hrm, I really not know what is this, ie, seems as MAC address:
      // 6 132.181.156.89.235.128 84:B5:9C:59:EB:80
      continue;
    }
    $lldp_array[$lldp_index]['lldpRemMan'][$addr] = $entry; // For multiple entries
  }
}

print_debug_vars($lldp_array, 1);

if ($lldp_array) {
  $dot1d_array = snmp_cache_table($device, "dot1dBasePortIfIndex", array(), "BRIDGE-MIB");
  //$lldp_local_array = snmpwalk_cache_oid($device, "lldpLocalSystemData", array(), "LLDP-MIB");
  $lldp_local_array = snmpwalk_cache_oid($device, "lldpLocPortEntry", array(), "LLDP-MIB");

  foreach ($lldp_array as $index => $lldp) {
    if (str_contains_array($index, '.')) {
      // This is correct RFC case:
      // LLDP-MIB::lldpRemChassisIdSubtype.0.0.1 = INTEGER: macAddress(4)
      list($lldpRemTimeMark, $lldpRemLocalPortNum, $lldpRemIndex) = explode('.', $index);
    } else {
      // Incorrect case (ie on old RouterOS):
      // LLDP-MIB::lldpRemChassisIdSubtype.1495 = INTEGER: macAddress(4)
      $lldpRemTimeMark = 0;
      $lldpRemLocalPortNum = 0;
      $lldpRemIndex = $index;
    }

    // Detect local device port
    $port = NULL;

    // Prefer by LLDP-MIB
    if (!$port && !empty($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortDesc'])) {
      //lldpLocPortIdSubtype.15 = interfaceName
      //lldpLocPortIdSubtype.16 = interfaceName
      //lldpLocPortId.15 = "Te1/15"
      //lldpLocPortId.16 = "Te1/16"
      //lldpLocPortDesc.15 = TenGigabitEthernet1/15
      //lldpLocPortDesc.16 = TenGigabitEthernet1/16
      $ifName = $lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortDesc'];
      if ($ifName === 'OOBM') {
        // procurve:
        // ifDescr.5481 = Out Of Band Management Port
        // ifDescr.5492 = Out Of Band Management loopback Interface
        // ifName.5481 = oobm0
        // ifName.5492 = lo0
        // lldpLocPortIdSubtype.4000 = local
        // lldpLocPortId.4000 = "4000"
        // lldpLocPortDesc.4000 = OOBM
        $ifName .= '0';
      }
      $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND (`ifName`= ? OR `ifDescr` = ? OR `port_label_short` = ?)", [ $device['device_id'], 0, $ifName, $ifName, $ifName ]);
    }

    // By BRIDGE-MIB (Warning, seems as more hard on multiple platforms not correctly association with ifIndex for LLDP)
    if (!$port && is_numeric($dot1d_array[$lldpRemLocalPortNum]['dot1dBasePortIfIndex']) &&
        !in_array($device['os'], [ 'junos', 'dell-os10' ])) { // Incorrect association on this platforms
      // Get the port using BRIDGE-MIB
      $ifIndex = $dot1d_array[$lldpRemLocalPortNum]['dot1dBasePortIfIndex'];

      $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND `ifIndex` = ? AND `ifDescr` NOT LIKE 'Vlan%'", [ $device['device_id'], 0, $ifIndex ]);
    }

    // last try by lldpLocPortId, also see below for remote port
    if (!$port) {
      switch ($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortIdSubtype']) {
        case 'interfaceName':
          $ifName = snmp_hexstring($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortId']);
          $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?)", [ $device['device_id'], 0, $ifName, $ifName, $ifName ]);
          break;

        case 'interfaceAlias':
          $ifAlias = snmp_hexstring($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortId']);
          $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND `ifAlias` = ?", [ $device['device_id'], 0, $ifAlias ]);
          break;

        case 'macAddress':
          $ifPhysAddress = strtolower(str_replace(' ', '', $lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortId']));
          $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND `ifPhysAddress` = ?", [ $device['device_id'], 0, $ifPhysAddress ]);
          break;

        case 'networkAddress':
          $ip = snmp_hexstring($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortId']);
          /*
          $ip_version = get_ip_version($id);
          if ($ip_version)
          {
            $ip = ($ip_version === 6 ? Net_IPv6::uncompress($id, TRUE) : $id);
            $port = dbFetchRow("SELECT * FROM `ipv".$ip_version."_addresses` LEFT JOIN `ports` USING (`port_id`) WHERE `ipv".$ip_version."_address` = ? AND `device_id` = ?", array($ip, $device['device_id']));
          }
          unset($id, $ip);
          */
          $peer_where = generate_query_values($device['device_id'], 'device_id'); // Additional filter for include self IPs
          // Fetch all devices with peer IP and filter by UP
          if ($ids = get_entity_ids_ip_by_network('port', $ip, $peer_where)) {
            $port = get_port_by_id_cache($ids[0]);
          }

          break;
      }
    }

    // Ohh still unknown port? this is not should happen, but this derp LLDP implementation on your device
    if (!$port && is_numeric($lldpRemLocalPortNum)) {
      print_debug('WARNING. Local port for neighbour not found, used incorrect lldpRemLocalPortNum as ifIndex.');
      $ifIndex = $lldpRemLocalPortNum; // This is incorrect, not really ifIndex, but seems sometime this numbers same
      $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND `ifIndex` = ? AND `ifDescr` NOT LIKE 'Vlan%'", [ $device['device_id'], 0, $ifIndex ]);
    }

    $remote_device_id = FALSE;
    $remote_port_id   = NULL;

    // Sometime lldpRemPortDesc is not set
    if (!isset($lldp['lldpRemPortDesc'])) {
      $lldp['lldpRemPortDesc'] = '';
    }

    $remote_mac = $lldp['lldpRemChassisIdSubtype'] === 'macAddress' ? $lldp['lldpRemChassisId'] : NULL;

    // lldpRemPortId can be hex string
    if ($lldp['lldpRemPortIdSubtype'] !== 'macAddress') {
      // On Extreme platforms, they remove the leading 1: from ports. Put it back if there isn't a :.
      if (preg_match ('/^ExtremeXOS.*$/', $lldp['lldpRemSysDesc'])) {
        if (!preg_match ('/\:/',$lldp['lldpRemPortId'])) {
          $lldp['lldpRemPortId'] = '1:'.$lldp['lldpRemPortId'];
        }
      } else {
        //$lldp['lldpRemPortId'] = snmp_hexstring($lldp['lldpRemPortId']);
      }
    } elseif (safe_empty($remote_mac)) {
      $remote_mac = $lldp['lldpRemPortId'];
    }

    // Clean MAC & IP
    if (isset($lldp['lldpRemMan']) && count($lldp['lldpRemMan']) > 1) {
      // Multiple IP addresses.. detect best?
      foreach (array_keys($lldp['lldpRemMan']) as $addr) {
        $addr_version = get_ip_version($addr);
        $addr_type = get_ip_type($addr);
        if (in_array($addr_type, [ 'unspecified', 'loopback', 'reserved', 'multicast' ])) {
          continue;
        }
        if ($addr_version == 6 && $addr_type === 'link-local') {
          continue;
        }
        if ($addr_type === 'unicast') {
          // Prefer IPv4/IPv6 unicast
          $lldp['lldpRemManAddr'] = $addr;
          break;
        }
        if ($addr_version == 4) {
          // Than prefer IPv4
          $lldp['lldpRemManAddr'] = $addr;
          break;
        }
        $lldp['lldpRemManAddr'] = $addr;
      }
      print_debug("Multiple remote IP addresses detect, selected: $addr");
      print_debug_vars($lldp);
    }
    if (isset($lldp['lldpRemManAddr'])) {
      $lldp['lldpRemManAddr'] = hex2ip($lldp['lldpRemManAddr']);
    }

    // Cumulus Linux have empty lldpRemSysDesc
    if (!isset($lldp['lldpRemSysDesc']) && isset($lldp['lldpXMedRemModelName'])) {
      $lldp['lldpRemSysDesc'] = trim($lldp['lldpXMedRemMfgName'] . ' ' . $lldp['lldpXMedRemModelName']);
    }
    $lldp['lldpRemSysVersion'] = safe_empty($lldp['lldpXMedRemSoftwareRev']) ? NULL : $lldp['lldpXMedRemSoftwareRev'];

    // Remote sysname is MAC in some cases
    // lldpRemChassisIdSubtype.0.16.1 = macAddress
    // lldpRemChassisId.0.16.1 = "40 E3 D6 CE 8A 9A "
    // lldpRemPortIdSubtype.0.16.1 = macAddress
    // lldpRemPortId.0.16.1 = "40 E3 D6 CE 8A 9A "
    // lldpRemSysName.0.16.1 = 40:e3:d6:ce:8a:9a
    // lldpRemManAddrIfId.0.16.1.1.4.10.10.14.132 = 8
    if (preg_match('/^[a-f\d]{2}([: ][a-f\d]{2}){5}$/', $lldp['lldpRemSysName']) && get_ip_version($lldp['lldpRemManAddr'])) {
      // Replace by IP address for better discovery
      print_debug("LLDP hostname replaced: ".$lldp['lldpRemSysName']." -> ".$lldp['lldpRemManAddr']);
      $lldp['lldpRemSysName'] = $lldp['lldpRemManAddr'];
    } elseif (safe_empty($lldp['lldpRemSysName']) && !safe_empty($lldp['lldpRemSysDesc'])) {
      if ($lldp['lldpRemChassisIdSubtype'] === 'macAddress') {
        // use mac address instead empty hostname:
        // lldpRemChassisIdSubtype.89.3.1 = macAddress
        // lldpRemChassisId.89.3.1 = "6C B0 CE 12 A9 A8 "
        // lldpRemPortIdSubtype.89.3.1 = local
        // lldpRemPortId.89.3.1 = "g25"
        // lldpRemPortDesc.89.3.1 = g25
        // lldpRemSysName.89.3.1 =
        // lldpRemSysDesc.89.3.1 = GS724Tv4 ProSafe 24-port Gigabit Ethernet Smart Switch, 6.3.1.4, B1.0.0.4
        // lldpRemManAddrIfId.89.3.1.1.4.192.168.0.239 = 51
        $lldp['lldpRemSysName'] = str_replace(array( ' ', '-' ), '', strtolower($lldp['lldpRemChassisId']));
      } elseif ($lldp['lldpRemChassisIdSubtype'] === 'networkAddress' &&
                preg_match('/^01 (?<ip>([A-F\d]{2}\s?){4})$/', $lldp['lldpRemChassisId'], $matches)) {
        $lldp['lldpRemSysName'] = hex2ip($matches['ip']);
      }
    }

    // Try find remote device and check if already cached
    $remote_device_id = get_autodiscovery_device_id($device, $lldp['lldpRemSysName'], $lldp['lldpRemManAddr'], $remote_mac);
    if (is_null($remote_device_id) &&                                                         // NULL - never cached in other rounds
        check_autodiscovery($lldp['lldpRemSysName'], $lldp['lldpRemManAddr'])) // Check all previous autodiscovery rounds
    {
      // Neighbour never checked, try autodiscovery
      $remote_device_id = autodiscovery_device($lldp['lldpRemSysName'], $lldp['lldpRemManAddr'], 'LLDP', $lldp['lldpRemSysDesc'], $device, $port);
    }

    if ($remote_device_id) {
      $if = $lldp['lldpRemPortDesc'];
      $id = trim($lldp['lldpRemPortId']);

      // lldpPortIdSubtype   -> lldpPortId
      //  interfaceAlias(1), ->  ifAlias
      //  portComponent(2),  ->  entPhysicalAlias
      //  macAddress(3),     ->  ifPhysAddress
      //  networkAddress(4), ->  IP address
      //  interfaceName(5),  ->  ifName
      //  agentCircuitId(6), ->  agent-local identifier of the circuit (defined in RFC 3046) (FIXME, not know)
      //  local(7)           ->  ifIndex
      switch ($lldp['lldpRemPortIdSubtype'])
      {
        case 'interfaceAlias':
          $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE (`ifAlias` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?", [ $id, $if, $if, $remote_device_id, 0 ]);
          break;

        case 'interfaceName':
          // Try lldpRemPortId
          $query = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
          $remote_port_id = dbFetchCell($query, array($id, $id, $id, $remote_device_id, 0));
          if (!$remote_port_id && strlen($if))
          {
            // Try same by lldpRemPortDesc
            $remote_port_id = dbFetchCell($query, array($if, $if, $if, $remote_device_id, 0));
          }
          break;

        case 'macAddress':
          $remote_port_id = get_port_id_by_mac($remote_device_id, $id);
          break;

        case 'networkAddress':
          $ip_version = get_ip_version($id);
          if ($ip_version)
          {
            // Try by IP
            $peer_where = generate_query_values($remote_device_id, 'device_id'); // Additional filter for include self IPs
            // Fetch all devices with peer IP and filter by UP
            if ($ids = get_entity_ids_ip_by_network('port', $id, $peer_where))
            {
              $remote_port_id = $ids[0];
              //$port = get_port_by_id_cache($ids[0]);
            }
          }
          break;

        case 'local':
          // local not always ifIndex or FIXME (see: http://jira.observium.org/browse/OBSERVIUM-1716)
          if (!ctype_digit($id))
          {
            // Not sure what should be if $id ifName and it just numeric
            $query = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
            $remote_port_id = dbFetchCell($query, array($id, $id, $id, $remote_device_id, 0));
            if (!$remote_port_id)
            {
              // Try same by lldpRemPortDesc
              $remote_port_id = dbFetchCell($query, array($if, $if, $if, $remote_device_id, 0));
            }
          }
        case 'ifIndex':
          // These cases are handled by the ifDescr/ifIndex combination fallback below
          break;

        default:
          break;
      }

      if (!$remote_port_id && is_numeric($id)) // Not found despite our attempts above - fall back to try matching with ifDescr/ifIndex
      {
        $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE (`ifIndex`= ? OR `ifDescr` = ?) AND `device_id` = ? AND `deleted` = ?", array($id, $if, $remote_device_id, 0));
      }

      if (!$remote_port_id) // Still not found?
      {
        if ($lldp['lldpRemChassisIdSubtype'] === 'macAddress')
        {
          // Find the port by chassis MAC address, only use this if exactly 1 match is returned, otherwise we'd link wrongly - think switches with 1 global MAC on all ports.
          $remote_port_id = get_port_id_by_mac($remote_device_id, $lldp['lldpRemChassisId']);
        } else {
          // Last chance
          $query = 'SELECT `port_id` FROM `ports` WHERE (`ifName` IN (?, ?) OR `ifDescr` IN (?, ?) OR `port_label_short` IN (?, ?)) AND `device_id` = ? AND `deleted` = ?';
          $remote_port_id = dbFetchCell($query, array($id, $if, $id, $if, $id, $if, $remote_device_id, 0));
        }
      }

      // Still not found? Seems as incorrect remote device :/
      if (!$remote_port_id)
      {
        print_debug("WARNING. Remote device found in db, but remote port not found. Seems as incorrect remote device association.");
      }
    }

    // FIXME. We can use lldpRemSysCapEnabled as platform, but they use BITS textual conversion:
    // LLDP-MIB::lldpRemSysCapEnabled.0.5.3 = BITS: 20 00 bridge(2)
    // LLDP-MIB::lldpRemSysCapEnabled.0.5.3 = "20 00 "
    $neighbour = [
      'remote_port_id'  => $remote_port_id,
      'remote_hostname' => $lldp['lldpRemSysName'],
      'remote_port'     => $lldp['lldpRemPortId'],
      'remote_platform' => $lldp['lldpRemSysDesc'],
      'remote_version'  => $lldp['lldpRemSysVersion'], //NULL,
      'remote_address'  => $lldp['lldpRemManAddr']
    ];
    discover_neighbour($port, 'lldp', $neighbour);
  }
}

// EOF
