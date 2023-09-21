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
DCN-MIB::vlanName.1 = STRING: "default"
DCN-MIB::vlanName.41 = STRING: "p2p-ats327"
DCN-MIB::vlanName.52 = STRING: "mgmt25"
DCN-MIB::vlanPortNumber.1 = INTEGER: 52
DCN-MIB::vlanPortNumber.41 = INTEGER: 1
DCN-MIB::vlanPortNumber.52 = INTEGER: 24
DCN-MIB::vlanRowStatus.1 = INTEGER: active(1)
DCN-MIB::vlanRowStatus.41 = INTEGER: active(1)
DCN-MIB::vlanRowStatus.52 = INTEGER: active(1)
*/

$vlan_oids = snmpwalk_cache_oid($device, 'vlanInfoTable', [], 'DCN-MIB');
print_debug_vars($vlan_oids);

$vtp_domain_index = '1'; // Yep, always use domain index 1

foreach ($vlan_oids as $vlan_num => $vlan) {
    // Skip not exist vlans
    if ($vlan['vlanRowStatus'] != 'active') {
        continue;
    }

    $vlan_array = [
      'ifIndex'     => $vlan['vlanPortNumber'],
      'vlan_domain' => $vtp_domain_index,
      'vlan_vlan'   => $vlan_num,
      'vlan_name'   => strlen($vlan['vlanName']) ? $vlan['vlanName'] : "Vlan $vlan_num",
      //'vlan_mtu'    => $vlan[''],
      'vlan_type'   => 'ethernet',
      'vlan_status' => 'operational'
    ];

    $discovery_vlans[$vtp_domain_index][$vlan_num] = $vlan_array;
}

$ports_vlans_oids = snmpwalk_cache_oid($device, 'portMode', [], 'DCN-MIB');
$oids             = [];
foreach ($ports_vlans_oids as $ifIndex => $entry) {
    if ($entry['portMode'] == 'trunk') {
        $oids[] = 'portTrunkAllowedvlan';
    } elseif ($entry['portMode'] == 'hybrid') {
        $oids[] = 'portHybridTaggedAllowedvlan';
    }
}
foreach (array_unique($oids) as $oid) {
    $ports_vlans_oids = snmpwalk_cache_oid($device, $oid, $ports_vlans_oids, 'DCN-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);
}
print_debug_vars($ports_vlans_oids);

foreach ($ports_vlans_oids as $ifIndex => $entry) {

    if ($entry['portMode'] == 'trunk') {
        $binary = hex2binmap($entry['portTrunkAllowedvlan']);
    } elseif ($entry['portMode'] == 'hybrid') {
        $binary = hex2binmap($entry['portHybridTaggedAllowedvlan']);
    } else {
        continue;
    }

    // Assign binary vlans map to ports
    $length = strlen($binary);
    for ($i = 0; $i < $length; $i++) {
        if ($binary[$i] && $i > 0) {
            $vlan_num = $i;

            //print_debug("ifIndex = $ifIndex, \$i = $i, mode {$entry['portMode']}");
            if (isset($discovery_vlans[$vtp_domain_index][$vlan_num])) {
                $discovery_ports_vlans[$ifIndex][$vlan_num] = ['vlan' => $vlan_num];
            }
        }
    }

}

// EOF
