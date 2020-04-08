<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// lldpRemoteSystemsData: lldpRemTable + lldpRemManAddrTable + lldpRemUnknownTLVTable + lldpRemOrgDefInfoTable
$lldpRemTable_oids = array('lldpRemChassisIdSubtype', 'lldpRemChassisId',
                           'lldpRemPortIdSubtype', 'lldpRemPortId', 'lldpRemPortDesc',
                           'lldpRemSysName');
$lldp_array = array();
foreach ($lldpRemTable_oids as $oid)
{
  $lldp_array = snmpwalk_cache_threepart_oid($device, $oid, $lldp_array, "LLDP-MIB");

  if (empty($lldp_array)) { break; } // Stop walk if no data
}
if ($lldp_array)
{
  // lldpRemSysDesc can be multilined
  //$lldp_array = snmpwalk_cache_threepart_oid($device, 'lldpRemPortDesc', $lldp_array, "LLDP-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
  $lldp_array = snmpwalk_cache_threepart_oid($device, 'lldpRemSysDesc', $lldp_array, "LLDP-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
}

print_debug_vars($lldp_array, 1);

if ($lldp_array)
{
  $dot1d_array = snmp_cache_table($device, "dot1dBasePortIfIndex", array(), "BRIDGE-MIB");
  //$lldp_local_array = snmpwalk_cache_oid($device, "lldpLocalSystemData", array(), "LLDP-MIB");
  $lldp_local_array = snmpwalk_cache_oid($device, "lldpLocPortEntry", array(), "LLDP-MIB");

  foreach ($lldp_array as $lldpRemTimeMark => $lldp_if_array)
  {
    foreach ($lldp_if_array as $lldpRemLocalPortNum => $lldp_instance)
    {
      // Detect local device port
      unset($port);
      if (is_numeric($dot1d_array[$lldpRemLocalPortNum]['dot1dBasePortIfIndex']) && $device['os'] != "junos") // FIXME why the junos exclude?
      {
        // Get the port using BRIDGE-MIB
        $ifIndex = $dot1d_array[$lldpRemLocalPortNum]['dot1dBasePortIfIndex'];

        $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ? AND `ifDescr` NOT LIKE 'Vlan%'", array($device['device_id'], $ifIndex));
      }

      // If BRIDGE-MIB failed, get the port using pure LLDP-MIB
      if (!$port && !empty($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortDesc']))
      {
        //lldpLocPortIdSubtype.15 = interfaceName
        //lldpLocPortIdSubtype.16 = interfaceName
        //lldpLocPortId.15 = "Te1/15"
        //lldpLocPortId.16 = "Te1/16"
        //lldpLocPortDesc.15 = TenGigabitEthernet1/15
        //lldpLocPortDesc.16 = TenGigabitEthernet1/16
        $ifName = $lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortDesc'];
        $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND (`ifName`= ? OR `ifDescr` = ?)", array($device['device_id'], $ifName, $ifName));
      }

      // last try by lldpLocPortId, also see below for remote port
      if (!$port)
      {
        switch ($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortIdSubtype'])
        {
          case 'interfaceName':
            $ifName = snmp_hexstring($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortId']);
            $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?)", array($device['device_id'], $ifName, $ifName, $ifName));
            break;
          case 'interfaceAlias':
            $ifAlias = snmp_hexstring($lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortId']);
            $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `ifAlias` = ?", array($device['device_id'], $ifAlias));
            break;
          case 'macAddress':
            $ifPhysAddress = strtolower(str_replace(' ', '', $lldp_local_array[$lldpRemLocalPortNum]['lldpLocPortId']));
            $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `ifPhysAddress` = ?", array($device['device_id'], $ifPhysAddress));
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
            $peer_where = generate_query_values($device['device_id'], 'device_id'); // Additional filter for exclude self IPs
            // Fetch all devices with peer IP and filter by UP
            if ($ids = get_entity_ids_ip_by_network('port', $ip, $peer_where))
            {
              $port = get_port_by_id_cache($ids[0]);
              /*
              $remote_device = $ids[0];
              if (count($ids) > 1)
              {
                // If multiple same IPs found, get first NOT disabled or down
                foreach ($ids as $id)
                {
                  $tmp_device = device_by_id_cache($id);
                  if (!$tmp_device['disabled'] && $tmp_device['status'])
                  {
                    $remote_device = $id;
                    break;
                  }
                }
              }
              */
            }

            break;
        }
      }

      // Ohh still unknown port? this is not should happen, but this derp LLDP implementatioun on your device
      if (!$port)
      {
        print_debug('WARNING. Local port for neighbour not found, used incorrect lldpRemLocalPortNum as ifIndex.');
        $ifIndex = $lldpRemLocalPortNum; // This is incorrect, not really ifIndex, but seems sometime this numbers same
        $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ? AND `ifDescr` NOT LIKE 'Vlan%'", array($device['device_id'], $ifIndex));
      }

      foreach ($lldp_instance as $entry_instance => $lldp)
      {
        $remote_device_id = FALSE;
        $remote_port_id   = NULL;

        // Sometime lldpRemPortDesc is not set
        if (!isset($lldp['lldpRemPortDesc']))
        {
          $lldp['lldpRemPortDesc'] = '';
        }

        // lldpRemPortId can be hex string
        if ($lldp['lldpRemPortIdSubtype'] != 'macAddress')
        {
          // On Extreme platforms, they remove the leading 1: from ports. Put it back if there isn't a :.
          if (preg_match ('/^ExtremeXOS.*$/',$lldp['lldpRemSysDesc'])) {
            if (!preg_match ('/\:/',$lldp['lldpRemPortId'])) {
              $lldp['lldpRemPortId'] = '1:'.$lldp['lldpRemPortId'];
            }
          } else {
            //$lldp['lldpRemPortId'] = snmp_hexstring($lldp['lldpRemPortId']);
          }
        }

        if (is_valid_hostname($lldp['lldpRemSysName']))
        {
          if (isset($GLOBALS['cache']['discovery-protocols'][$lldp['lldpRemSysName']]))
          {
            // This hostname already checked, skip discover
            $remote_device_id = $GLOBALS['cache']['discovery-protocols'][$lldp['lldpRemSysName']];
          } else {
            $remote_device = dbFetchRow("SELECT `device_id`, `hostname` FROM `devices` WHERE `sysName` = ? OR `hostname` = ?", array($lldp['lldpRemSysName'], $lldp['lldpRemSysName']));
            $remote_device_id = $remote_device['device_id'];

            // Overwrite remote hostname with the one we know, for devices that we identify by sysName
            if ($remote_device['hostname']) { $lldp['lldpRemSysName'] = $remote_device['hostname']; }

            // If we don't know this device, try to discover it, as long as it's not matching our exclusion filters
            if (!$remote_device_id && !is_bad_xdp($lldp['lldpRemSysName'], $lldp['lldpRemSysDesc'])) // NOTE, LLDP not have any usable Platform name, here we use lldpRemSysDesc
            {
              $remote_device_id = discover_new_device($lldp['lldpRemSysName'], 'xdp', 'LLDP', $device, $port);
            }

            // Cache remote device ID for other protocols
            $GLOBALS['cache']['discovery-protocols'][$lldp['lldpRemSysName']] = $remote_device_id;
          }
        } else {
          // Try to find remote host by remote chassis mac address from DB
          if (empty($lldp['lldpRemSysName']) && $lldp['lldpRemChassisIdSubtype'] == 'macAddress')
          {
            $remote_mac = str_replace(array(' ', '-', ':'), '', strtolower($lldp['lldpRemChassisId']));
            $remote_device_id = dbFetchCell("SELECT `device_id` FROM `ports` WHERE `deleted` = '0' AND `ifPhysAddress` = ? LIMIT 1;", array($remote_mac));
            if ($remote_device_id)
            {
              $remote_device_hostname = device_by_id_cache($remote_device_id);
              if ($remote_device_hostname['hostname'])
              {
                $lldp['lldpRemSysName'] = $remote_device_hostname['hostname'];
              }
            }
          }
        }

        if ($remote_device_id)
        {
          $if = $lldp['lldpRemPortDesc'];
          $id = $lldp['lldpRemPortId'];

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
              $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE (`ifAlias` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ?", array($id, $if, $if, $remote_device_id));
              break;
            case 'interfaceName':
              // Try lldpRemPortId
              $query = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ?';
              $remote_port_id = dbFetchCell($query, array($id, $id, $id, $remote_device_id));
              if (!$remote_port_id)
              {
                // Try same by lldpRemPortDesc
                $remote_port_id = dbFetchCell($query, array($if, $if, $if, $remote_device_id));
              }
              break;
            case 'macAddress':
              $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE `ifPhysAddress` = ? AND `device_id` = ?", array(strtolower(str_replace(array(' ', '-'), '', $id)), $remote_device_id));
              break;
            case 'networkAddress':
              $ip_version = get_ip_version($id);
              if ($ip_version)
              {
                $ip = ($ip_version === 6 ? Net_IPv6::uncompress($id, TRUE) : $id);
                $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ipv".$ip_version."_addresses` LEFT JOIN `ports` USING (`port_id`) WHERE `ipv".$ip_version."_address` = ? AND `device_id` = ?", array($ip, $remote_device_id));
              }
              break;
            case 'local':
              // local not always ifIndex or FIXME (see: http://jira.observium.org/browse/OBSERVIUM-1716)
              if (!ctype_digit($id))
              {
                // Not sure what should be if $id ifName and it just numeric
                $query = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ?';
                $remote_port_id = dbFetchCell($query, array($id, $id, $id, $remote_device_id));
                if (!$remote_port_id)
                {
                  // Try same by lldpRemPortDesc
                  $remote_port_id = dbFetchCell($query, array($if, $if, $if, $remote_device_id));
                }
              }
            case 'ifIndex':
              // These cases are handled by the ifDescr/ifIndex combination fallback below
            default:
              break;
          }

          if (!$remote_port_id) // Not found despite our attempts above - fall back to try matching with ifDescr/ifIndex
          {
            $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE (`ifIndex`= ? OR `ifDescr` = ?) AND `device_id` = ?", array($id, $if, $remote_device_id));
          }

          if (!$remote_port_id) // Still not found?
          {
            if ($lldp['lldpRemChassisIdSubtype'] == 'macAddress')
            {
              // Find the port by chassis MAC address, only use this if exactly 1 match is returned, otherwise we'd link wrongly - think switches with 1 global MAC on all ports.
              $remote_chassis_id = strtolower(str_replace(array(' ', '-'),'',$lldp['lldpRemChassisId']));
              $remote_port_ids = dbFetchRows("SELECT `port_id` FROM `ports` WHERE `ifPhysAddress` = ? AND `device_id` = ?", array($remote_chassis_id, $remote_device_id));
              if (count($remote_port_ids) == 1) { $remote_port_id = $remote_port_ids[0]['port_id']; }
            } else {
              // Last chance
              $query = 'SELECT `port_id` FROM `ports` WHERE (`ifName` IN (?, ?) OR `ifDescr` IN (?, ?) OR `port_label_short` IN (?, ?)) AND `device_id` = ?';
              $remote_port_id = dbFetchCell($query, array($id, $if, $id, $if, $id, $if, $remote_device_id));
            }
          }
        } else {
          // FIXME why is this here?
          if (empty($lldp['lldpRemSysName']) && $lldp['lldpRemChassisIdSubtype'] == 'macAddress')
          {
            $lldp['lldpRemSysName'] = str_replace(array(' ', '-'), '', strtolower($lldp['lldpRemChassisId']));
          }
        }

        if (!is_bad_xdp($lldp['lldpRemSysName']) && is_numeric($port['port_id']) && !empty($lldp['lldpRemSysName']) && isset($lldp['lldpRemPortId']))
        {
          // FIXME. We can use lldpRemSysCapEnabled as platform, but they use BITS textual conversion:
          // LLDP-MIB::lldpRemSysCapEnabled.0.5.3 = BITS: 20 00 bridge(2)
          // LLDP-MIB::lldpRemSysCapEnabled.0.5.3 = "20 00 "

          discover_link($port, 'lldp', $remote_port_id, $lldp['lldpRemSysName'], $lldp['lldpRemPortId'], NULL, $lldp['lldpRemSysDesc']);
        }
      }
    }
  }
}

// EOF
