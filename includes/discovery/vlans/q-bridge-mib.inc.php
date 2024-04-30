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

if (is_device_mib($device, 'CISCO-VTP-MIB')) {
    // Q-BRIDGE-MIB is default mib, need excludes Cisco
    return;
}

$domain_index = '1';

if (safe_count($discovery_vlans[$domain_index]) &&
    is_device_mib($device, ['CISCOSB-vlan-MIB', 'RADLAN-vlan-MIB', 'Dell-vlan-MIB',
                            'DLINK-3100-vlan-MIB', 'EDGECORE-vlan-MIB', 'NETGEAR-RADLAN-vlan-MIB',
                            'A3COM-HUAWEI-LswVLAN-MIB', 'IEEE8021-Q-BRIDGE-MIB'])) {
    // Already discovered by RADLAN based vlans or IEEE8021-Q-BRIDGE-MIB
    return;
}

// Simplify dot1qVlanStaticTable walk
$dot1q_ports = snmpwalk_cache_oid($device, 'dot1qVlanStaticName', [], 'Q-BRIDGE-MIB');
//$dot1q_ports = snmpwalk_cache_oid($device, 'dot1qVlanStaticTable', array(), 'Q-BRIDGE-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);
if (!snmp_status()) {
    return;
}

$dot1q_ports = snmpwalk_cache_oid($device, 'dot1qVlanStaticRowStatus', $dot1q_ports, 'Q-BRIDGE-MIB');
$dot1q_ports = snmpwalk_cache_oid($device, 'dot1qVlanStaticEgressPorts', $dot1q_ports, 'Q-BRIDGE-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);

if (is_device_mib($device, 'JUNIPER-VLAN-MIB')) { // Unsure if other Juniper platforms "affected"
    // Fetch Juniper VLAN table for correct tag
    $dot1q_ports = snmpwalk_cache_oid($device, 'jnxExVlanTable', $dot1q_ports, 'JUNIPER-VLAN-MIB');
}

/* Base port ifIndex association */
$dot1d_baseports = snmp_cache_table($device, 'dot1dBasePortIfIndex', [], 'BRIDGE-MIB');

// Detect min ifIndex for vlan base ports
// Why, see here: http://jira.observium.org/browse/OBS-963
$use_baseports = count($dot1d_baseports) > 0;
/*
if ($use_baseports)
{
  $vlan_ifindex_min = $dot1d_baseports[key($dot1d_baseports)]['dot1dBasePortIfIndex']; // First element
  foreach ($dot1d_baseports as $entry)
  {
    // But min ifIndex can be in any entry
    $vlan_ifindex_min = min($vlan_ifindex_min, $entry['dot1dBasePortIfIndex']);
  }

} else {
  $vlan_ifindex_min = 0;
}*/
/* End base port ifIndex association */

$binary_debug = []; // DEBUG
foreach ($dot1q_ports as $vlan_num => $vlan) {
    $vlan['ifIndex'] = $vlan_num;

    if ($device['os'] === 'ftos') {
        // FTOS vlan fix
        // Q-BRIDGE-MIB::dot1qVlanStaticEgressPorts.1107787777, where 1107787777 is ifIndex for Vlan interface
        //IF-MIB::ifDescr.1107787777 = STRING: Vlan 1
        //IF-MIB::ifDescr.1107787998 = STRING: Vlan 222
        $vlan_num = rewrite_ftos_vlanid($device, $vlan_num);
        if (!is_numeric($vlan_num)) {
            continue; // Skip unknown
        }
    } elseif (isset($vlan['jnxExVlanTag'])) {
        // JunOS Vlan fix
        $vlan_num = $vlan['jnxExVlanTag'];
    }

    $vlan_array                                = [
      'ifIndex'     => $vlan['ifIndex'],
      'vlan_domain' => $domain_index,
      'vlan_vlan'   => $vlan_num,
      'vlan_name'   => $vlan['dot1qVlanStaticName'],
      //'vlan_mtu'    => '',
      'vlan_type'   => 'ethernet',
      'vlan_status' => 'operational'
    ];
    $discovery_vlans[$domain_index][$vlan_num] = $vlan_array;
    /* End vlans discovery */

    /* Per port vlans */

    // Convert hex to binary map
    $binary = hex2binmap($vlan['dot1qVlanStaticEgressPorts']);


    if ($device['os'] === 'ftos') {
        // FTOS devices use harder way for detect VLANs and associate ports
        // See: https://www.force10networks.com/CSPortal20/TechTips/0041B_displaying_vlan_ports.aspx
        // Port associations based on slot/port, each 12 hex pair (96 bin) is slot

        foreach (str_split($binary, 96) as $slot => $binary_map) {
            $length = strlen($binary_map);
            for ($i = 0; $i < $length; $i++) {
                if ($binary_map[$i]) {
                    // Now find slot/port from ifDescr
                    $port_map = '% ' . $slot . '/' . ($i + 1);
                    $ifIndex  = dbFetchCell("SELECT `ifIndex` FROM `ports` WHERE `device_id` = ? AND `ifDescr` LIKE ? AND `deleted` = ? LIMIT 1", [$device['device_id'], $port_map, 0]);

                    $discovery_ports_vlans[$ifIndex][$vlan_num] = [
                      'vlan' => $vlan_num,
                      // FIXME. move STP to separate table
                      //'baseport' => $vlan_port_id,
                      //'priority' => $vlan_port['dot1dStpPortPriority'],
                      //'state'    => $vlan_port['dot1dStpPortState'],
                      //'cost'     => $vlan_port['dot1dStpPortPathCost']
                    ];
                }
            }
        }

    } else {
        // All others

        // Assign binary vlans map to ports
        $length = strlen($binary);
        for ($i = 0; $i < $length; $i++) {
            if ($binary[$i]) {
                //$ifIndex = $i + $vlan_ifindex_min; // This is incorrect ifIndex association!
                if ($use_baseports) {
                    // Skip all unknown indexes (OBS-2958)
                    if (!isset($dot1d_baseports[$i + 1]['dot1dBasePortIfIndex'])) {
                        continue;
                    }
                    $ifIndex = $dot1d_baseports[$i + 1]['dot1dBasePortIfIndex'];
                } else {
                    $ifIndex = $i;
                }
                $binary_debug[$vlan_num][$i] = $ifIndex; // DEBUG

                $discovery_ports_vlans[$ifIndex][$vlan_num] = [
                  'vlan' => $vlan_num,
                  // FIXME. move STP to separate table
                  //'baseport' => $vlan_port_id,
                  //'priority' => $vlan_port['dot1dStpPortPriority'],
                  //'state'    => $vlan_port['dot1dStpPortState'],
                  //'cost'     => $vlan_port['dot1dStpPortPathCost']
                ];
            }
        }

    }
}
print_debug_vars($binary_debug); // DEBUG

// As last point validate access ports that vlan is added (some devices not report it in dot1qVlanStaticTable)
$dot1q_ports = snmpwalk_cache_oid($device, 'dot1qPvid', [], 'Q-BRIDGE-MIB');
foreach ($dot1q_ports as $entry) {
    $vlan_num = $entry['dot1qPvid'];
    if (!isset($discovery_vlans[$domain_index][$vlan_num])) {
        $vlan_array                                = [
          'vlan_domain' => $domain_index,
          'vlan_vlan'   => $vlan_num,
          'vlan_name'   => 'VLAN ' . $vlan_num,
          //'vlan_mtu'    => '',
          'vlan_type'   => 'ethernet',
          'vlan_status' => 'operational'
        ];
        $discovery_vlans[$domain_index][$vlan_num] = $vlan_array;
    }
}

echo(PHP_EOL);

// EOF
