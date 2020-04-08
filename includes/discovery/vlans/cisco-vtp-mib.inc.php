<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// Not sure why we check for VTP, but this data comes from that MIB, so... (I think just validate that here data exist)
$vtpversion = snmp_get_oid($device, 'vtpVersion.0', 'CISCO-VTP-MIB', NULL, OBS_SNMP_ALL_NUMERIC);

switch ($vtpversion)
{
  case 'none':
  case '1':
  case '2':
  case '3':
  case 'one':
  case 'two':
  case 'three':
    // FIXME - can have multiple VTP domains.
    $vtpdomains = snmpwalk_cache_oid($device, 'vlanManagementDomains', array(), 'CISCO-VTP-MIB');
    $vtpvlans = snmpwalk_cache_twopart_oid($device, 'vtpVlanEntry', array(), 'CISCO-VTP-MIB');

    foreach ($vtpdomains as $vtp_domain_index => $vtpdomain)
    {
      // Skip disabled vtp domains
      if (in_array($vtpdomain['managementDomainRowStatus'], array('notInService', 'notReady', 'destroy')))
      {
        continue;
      }

      if ($vtpdomain['managementDomainName'])
      {
        echo("(Domain $vtp_domain_index ".$vtpdomain['managementDomainName'].")");
      } else {
        echo("(Domain $vtp_domain_index".")");
      }
      foreach ($vtpvlans[$vtp_domain_index] as $vlan_id => $vlan)
      {
        $vlan_array = array('ifIndex'     => $vlan['vtpVlanIfIndex'],
                            'vlan_domain' => $vtp_domain_index,
                            'vlan_vlan'   => $vlan_id,
                            'vlan_name'   => $vlan['vtpVlanName'],
                            'vlan_mtu'    => $vlan['vtpVlanMtu'],
                            'vlan_type'   => $vlan['vtpVlanType'],
                            'vlan_status' => $vlan['vtpVlanState'],
                            'vlan_context' => 0); // Vlan context exist validated below
        $discovery_vlans[$vtp_domain_index][$vlan_id] = $vlan_array;

      }
    }
    break;
}

// Check if per port vlans (with contexts) supported by Q-BRIDGE-MIB
$check_ports_vlans = FALSE;
if (in_array($device['os'], array('ios', 'iosxe')) && is_device_mib($device, 'Q-BRIDGE-MIB'))
{
  // This shit only seems to work on Cisco (probably only IOS/IOS-XE)
  // But don't worry, walking do only if vlans previously found

  list($ios_version) = explode('(', $device['version']);

  if (strlen($device['snmp_context']))
  {
    // Already configured snmp context
    print_warning("WARNING: Device already configured with SNMP context, polling ports vlans not possible.");
  }
  else if ($device['snmp_version'] == 'v3' && $device['os'] == "ios" && ($ios_version * 10) <= 121)
  {
    // vlan context not worked on Cisco IOS <= 12.1 (SNMPv3)
    print_error("ERROR: For VLAN context to work on this device please use SNMP v2/v1 for this device (or upgrade IOS).");
  } else {
    // In that case - check per port vlans ;)
    $check_ports_vlans = TRUE;
  }
}

if ($check_ports_vlans && count($discovery_vlans)) // Per port vlans walking allowed (see above)
{
  // Fetch first domain index
  reset($discovery_vlans);
  $vtp_domain_index = key($discovery_vlans);

  foreach ($discovery_vlans[$vtp_domain_index] as $vlan_id => $entry)
  {
    /* Per port vlans */

    // /usr/bin/snmpbulkwalk -v2c -c kglk5g3l454@988  -OQUs  -m BRIDGE-MIB -M /opt/observium/mibs/ udp:sw2.ahf:161 dot1dStpPortEntry
    // /usr/bin/snmpbulkwalk -v2c -c kglk5g3l454@988  -OQUs  -m BRIDGE-MIB -M /opt/observium/mibs/ udp:sw2.ahf:161 dot1dBasePortEntry

    // FIXME - do this only when vlan type == ethernet?
    // Cisco IOS Vlans:
    // 0, 4095   For system use only. You cannot see or use these VLANs.
    // 1002-1005 Cisco defaults for FDDI and Token Ring. You cannot delete VLANs 1002-1005
    if (is_numeric($vlan_id) &&
        $vlan_id != 4095 && ($vlan_id < 1002 || $vlan_id > 1005)) // Ignore reserved VLAN IDs
    {

      $device_context = $device;
      // Add vlan context for snmp auth
      if ($device['snmp_version'] == 'v3')
      {
        $device_context['snmp_context'] = 'vlan-' . $vlan_id;
      } else {
        $device_context['snmp_context'] = $vlan_id;
      }
      $device_context['snmp_retries'] = 1;         // Set retries to 0 for speedup walking
      $vlan_data = snmpwalk_cache_oid($device_context, "dot1dBasePortIfIndex", array(), "BRIDGE-MIB");

      // Detection shit snmpv3 authorization errors for contexts
      if ($GLOBALS['exec_status']['exitcode'] != 0)
      {
        unset($device_context);
        if ($device['snmp_version'] == 'v3')
        {
          print_error("ERROR: For VLAN context to work on Cisco devices with SNMPv3, it is necessary to add 'match prefix' in snmp-server config.");
        } else {
          print_error("ERROR: Device does not support per-VLAN community.");
        }
        break;
      }
      $vlan_data = snmpwalk_cache_oid($device_context, "dot1dStpPortEntry", $vlan_data, "BRIDGE-MIB:Q-BRIDGE-MIB");
      unset($device_context);

      // At this point vlan context is validated and exist
      $discovery_vlans[$vtp_domain_index][$vlan_id]['vlan_context'] = 1;

      if ($vlan_data)
      {
        print_debug(str_pad("dot1d id", 10).str_pad("ifIndex", 10).str_pad("Port Name", 25).
                    str_pad("Priority", 10).str_pad("State", 15).str_pad("Cost", 10));
      }

      foreach ($vlan_data as $vlan_port_id => $vlan_port)
      {
        $ifIndex = $vlan_port['dot1dBasePortIfIndex'];
        $discovery_ports_vlans[$ifIndex][$vlan_id] = array('vlan'     => $vlan_id,
                                                           // FIXME. move STP to separate table
                                                           'baseport' => $vlan_port_id,
                                                           'priority' => $vlan_port['dot1dStpPortPriority'],
                                                           'state'    => $vlan_port['dot1dStpPortState'],
                                                           'cost'     => $vlan_port['dot1dStpPortPathCost']);
      }
    } else {
      unset($module_stats[$vlan_id]);
    }
    /* End per port vlans */
  }
}

echo(PHP_EOL);

// EOF
