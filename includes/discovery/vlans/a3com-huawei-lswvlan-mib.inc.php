<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$domain_index = '1';

$hwdot1qVlan = snmpwalk_cache_oid($device, 'hwdot1qVlanMIBTable', [], 'A3COM-HUAWEI-LswVLAN-MIB');

/* Base port ifIndex association */
$dot1d_baseports = snmp_cache_table($device, 'dot1dBasePortIfIndex', [], 'BRIDGE-MIB');

foreach ($hwdot1qVlan as $vlan_num => $vlan) {

    $vlan_array                                = [
      'ifIndex'     => $vlan['hwVlanInterfaceIndex'],
      'vlan_domain' => $domain_index,
      'vlan_vlan'   => $vlan_num,
      'vlan_name'   => $vlan['hwdot1qVlanName'],
      //'vlan_mtu'    => '',
      'vlan_type'   => $vlan['hwdot1qVlanType'],
      'vlan_status' => 'operational'
    ];
    $discovery_vlans[$domain_index][$vlan_num] = $vlan_array;

    // Convert hex to binary map
    $binary = hex2binmap($vlan['hwdot1qVlanPorts']);

    // Assign binary vlans map to ports
    $length = strlen($binary);
    for ($i = 0; $i < $length; $i++) {
        if ($binary[$i]) {

            //if ($use_baseports) {
            // Skip all unknown indexes (OBS-2958)
            if (!isset($dot1d_baseports[$i + 1]['dot1dBasePortIfIndex'])) {
                continue;
            }
            $ifIndex = $dot1d_baseports[$i + 1]['dot1dBasePortIfIndex'];
            // } else {
            //   $ifIndex = $i;
            // }
            //$binary_debug[$vlan_num][$i] = $ifIndex; // DEBUG

            $discovery_ports_vlans[$ifIndex][$vlan_num] = [
              'vlan' => $vlan_num,
            ];
        }
    }
}

// EOF
