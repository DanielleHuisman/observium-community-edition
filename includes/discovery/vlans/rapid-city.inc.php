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

/*
RAPID-CITY::rcVlanName.1 = STRING: Default
RAPID-CITY::rcVlanName.2 = STRING: VLAN-2
RAPID-CITY::rcVlanPortMembers.1 = Hex-STRING: 00
RAPID-CITY::rcVlanPortMembers.2 = Hex-STRING: 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
00 00 00 0C
RAPID-CITY::rcVlanStaticMembers.1 = Hex-STRING: 00
RAPID-CITY::rcVlanStaticMembers.2 = Hex-STRING: 00
*/

$vlan_oids = snmpwalk_cache_oid($device, 'rcVlanName', [], 'RAPID-CITY');

if (!snmp_status()) {
    return;
}

$vlan_oids = snmpwalk_cache_oid($device, 'rcVlanType', $vlan_oids, 'RAPID-CITY');
$vlan_oids = snmpwalk_cache_oid($device, 'rcVlanIfIndex', $vlan_oids, 'RAPID-CITY');
$vlan_oids = snmpwalk_cache_oid($device, 'rcVlanRowStatus', $vlan_oids, 'RAPID-CITY');
print_debug_vars($vlan_oids);

$vtp_domain_index = '1'; // Yep, always use domain index 1

foreach ($vlan_oids as $vlan_num => $vlan) {
    // Skip not exist vlans
    if (in_array($vlan['rcVlanRowStatus'], ['notInService', 'notReady', 'destroy'])) {
        continue;
    }

    $vlan_array                                    = ['ifIndex'     => $vlan['rcVlanIfIndex'],
                                                      'vlan_domain' => $vtp_domain_index,
                                                      'vlan_vlan'   => $vlan_num,
                                                      'vlan_name'   => $vlan['rcVlanName'],
                                                      //'vlan_mtu'    => $vlan[''],
                                                      'vlan_type'   => $vlan['rcVlanType'],
                                                      'vlan_status' => 'operational'];
    $discovery_vlans[$vtp_domain_index][$vlan_num] = $vlan_array;

}

/*
RAPID-CITY::rcVlanPortVlanIds.513 = Hex-STRING: 00 08
RAPID-CITY::rcVlanPortVlanIds.514 = Hex-STRING: 00 03 00 04 00 05 00 06
*/

$ports_vlans_oids = snmpwalk_cache_oid($device, 'rcVlanPortVlanIds', [], 'RAPID-CITY', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);
print_debug_vars($ports_vlans_oids);

foreach ($ports_vlans_oids as $ifIndex => $entry) {

    foreach (str_split(str_replace(' ', '', $entry['rcVlanPortVlanIds']), 4) as $vlan_hex) {
        $vlan_num = hexdec($vlan_hex);

        if (isset($discovery_vlans[$vtp_domain_index][$vlan_num])) {
            $discovery_ports_vlans[$ifIndex][$vlan_num] = ['vlan' => $vlan_num];
        }
    }
}

// EOF
