<?php
/*
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Simplify ieee8021QBridgeVlanStaticTable walk
$dot1q_ports = snmpwalk_cache_twopart_oid($device, 'ieee8021QBridgeVlanStaticName', [], 'IEEE8021-Q-BRIDGE-MIB');
//$dot1q_ports = snmpwalk_cache_twopart_oid($device, 'ieee8021QBridgeVlanStaticTable', [], 'IEEE8021-Q-BRIDGE-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);
if (!snmp_status()) {
    return;
}

$dot1q_ports = snmpwalk_cache_twopart_oid($device, 'ieee8021QBridgeVlanStaticRowStatus', $dot1q_ports, 'IEEE8021-Q-BRIDGE-MIB');
$dot1q_ports = snmpwalk_cache_twopart_oid($device, 'ieee8021QBridgeVlanStaticEgressPorts', $dot1q_ports, 'IEEE8021-Q-BRIDGE-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);

/* Base port ifIndex association */
$dot1d_baseports = snmp_cache_table($device, 'dot1dBasePortIfIndex', [], 'BRIDGE-MIB');

// Detect min ifIndex for vlan base ports
// Why, see here: http://jira.observium.org/browse/OBS-963
$use_baseports = safe_count($dot1d_baseports) > 0;
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

foreach ($dot1q_ports as $domain_index => $vlans) {
    $binary_debug = []; // DEBUG
    foreach ($vlans as $vlan_num => $vlan) {
        $vlan['ifIndex'] = $vlan_num;

        $vlan_array                                = [
          'ifIndex'     => $vlan['ifIndex'],
          'vlan_domain' => $domain_index,
          'vlan_vlan'   => $vlan_num,
          'vlan_name'   => $vlan['ieee8021QBridgeVlanStaticName'],
          //'vlan_mtu'    => '',
          'vlan_type'   => 'ethernet',
          'vlan_status' => 'operational'
        ];
        $discovery_vlans[$domain_index][$vlan_num] = $vlan_array;
        /* End vlans discovery */

        /* Per port vlans */

        // Convert hex to binary map
        $binary = hex2binmap($vlan['ieee8021QBridgeVlanStaticEgressPorts']);

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
    print_debug_vars($binary_debug); // DEBUG
}

// As last point validate access ports that vlan is added (some devices not report it in ieee8021QBridgeVlanStaticTable)
$dot1q_ports = snmpwalk_cache_twopart_oid($device, 'ieee8021QBridgePvid', [], 'IEEE8021-Q-BRIDGE-MIB');
foreach ($dot1q_ports as $domain_index => $vlans) {
    foreach ($vlans as $entry) {
        $vlan_num = $entry['ieee8021QBridgePvid'];
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
}

echo(PHP_EOL);

// EOF
